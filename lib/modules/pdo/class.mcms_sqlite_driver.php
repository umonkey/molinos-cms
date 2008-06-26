<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class mcms_sqlite_driver extends PDO_Singleton
{
  private $dbfile = null;

  public function __construct(array $conf)
  {
    $this->dbfile = trim($conf['path'], '/');
    $dsn = 'sqlite:'. $this->dbfile;

    if (!file_exists($this->dbfile)) {
      if ('conf/default.db' == $this->dbfile) {
        if (file_exists($dist = $this->dbfile .'.dist')) {
          if (is_writable(dirname(realpath($dist))))
            copy($dist, $this->dbfile);
        }
      }
    }

    try {
      parent::__construct($dsn, '', '');
    } catch (PDOException $e) {
      if (!in_array('sqlite', PDO::getAvailableDrivers()))
        throw new NotInstalledException();
      elseif (file_exists($conf['path']))
        throw new RuntimeException(t('Не удалось открыть базу данных.'));
      else
        throw new NotInstalledException();
    }

    $this->dbtype = 'SQLite';
  }

  public function exec($sql, array $params = null)
  {
    try {
      $sth = $this->prepare($sql);
      $sth->execute($params);
    } catch (PDOException $e) {
      $info = $this->errorInfo();

      switch ($info[1]) {
      case 1: // General error: 1 no such table: xyz. (или no such column)
        if (preg_match("/(no such column:|has no column named)\s*(\S+)/i", $info[2], $matches)) {
          $cname = trim($matches[2]);

          //В SQlite в $info имя таблицы не содержится, надо проанализировать sql-запрос
          //Если в sql-запросе имеется строка node__idx_*** -
          //считаем, что это индексная таблица, и пытаемся её создать заново
          if (preg_match("/node__idx_(\S+)/i", $sql, $matches)) {
            $node = Node::load(array('class' => 'type', 'name' => $matches[1]));
            if (!empty($node)) {
              mcms::log('SQLite', $matches[1] .': updating index structure');
              $node->recreateIdxTable($matches[1]);
              return self::exec($sql, $params);
            }
          } else { // Это не индексная таблица, а одна из основных
            $re = '@(FROM|UPDATE|JOIN|INTO)\s+`([^`]+)`@i';

            if (!preg_match_all($re, $sql, $m)) {
              mcms::log('SQLite', 'could not find table names in SQL');
            } else {
              TableManager::upgradeTables($m[2]);

              mcms::log('SQLite', 're-running query: '. $sql);

              $sth = $this->prepare($sql);
              $sth->execute($params);
            }
          }
        }

        if (false !== strstr($info[2], 'no such table')) {
          if (preg_match("/no such table:\s*(\S+)/i", $info[2], $matches)) {
            if (preg_match("/node__idx_(\S+)/i", $sql, $tblmatches)) {
              //для индексных таблиц свой механизм пересоздания
               $node = Node::load(array('class' => 'type', 'name' => $tblmatches[1]));
               if (!empty($node)) {
                 $node->recreateIdxTable($tblmatches[1]);
                 return self::exec($sql, $params);
               }
            }
            else {
              TableManager::create($matches[1]);
              return self::exec($sql, $params);
            }
          }
          throw new TableNotFoundException(trim(strrchr($info[2], ' ')), $sql, $params);
        }

        throw new McmsPDOException($e, $sql);
        break;

      case 8:
        throw new ReadOnlyDatabaseException();

      default:
        throw new McmsPDOException($e, $sql);
      }
    }

    return $sth;
  }

  public function prepare($sql)
  {
    if (false !== strstr($sql, 'UTC_TIMESTAMP()'))
      $sql = str_replace('UTC_TIMESTAMP()', '\''. date('Y-m-d H:i:s', time() - date('Z', time())) .'\'', $sql);

    return parent::prepare($sql);
  }

  public function clearDB()
  {
  	// Прежде чем все похерить, стоит сделать резервную копию.
  	$this->makeBackup();

    $sql = "SELECT `tbl_name` FROM `sqlite_master` WHERE `type` = 'table'";
    $rows = $this->getResults($sql);

    foreach ($rows as $k => $el) {
      $sql = "DROP TABLE `{$el['tbl_name']}`";
      $this->exec($sql);
      $this->commit();
    }

    $sql = "SELECT `name` FROM `sqlite_master` WHERE `type` = 'index'";
    $rows = $this->getResults($sql);

    foreach ($rows as $k => $el) {
      $sql = "DROP INDEX `{$el['name']}`";
      $this->exec($sql);
      $this->commit();
    }
  }

  public function makeBackup()
  {
    if ((null !== $this->dbfile) and file_exists($this->dbfile) and filesize($this->dbfile) > 0)
      copy($this->dbfile, $this->dbfile .'.'. strftime('%Y%m%d%H%M%S'));
  }
  
  public function getTableInfo($name)
  {
    $indexes = array();
    $sql = "SELECT * FROM `sqlite_master` WHERE `tbl_name` = '{$name}' AND `type` = 'index'";
    $rows = $this->getResults($sql);

    foreach ($rows as $k => $el) {
      $str = $el['sql'];
      $col = preg_match("/\((.+)\)/", $str, $matches);
      $col = $matches[1];
      $col = str_replace('`', '', $col);
      $indexes[$col] = 1;
    }

    // получим саму таблицу
    $sql = "SELECT * FROM `sqlite_master` WHERE `tbl_name` = '{$name}' AND `type` = 'table'";
    $rows = $this->getResults($sql);

    if (empty($rows))
      return false;

    $sql = $rows[0]['sql'];

    $sql = strstr($sql,'(');
    $sql = substr($sql, 1);
    $fields = preg_split("/,(?!\d)/", $sql); // чтобы не было сплитования в случае DECIMAL(10,2)
    $columns = array();

    //Сейчас имеем базг в SQLite - $rows[0]['sql'] содержит на конце непарную ')',
    //что вызывает глюки, если у  нас для последнего поля не указан размер. Надо эту
    //скобку удалить, то только в том случае, если она действительно
    //несимметрична (т.е. число скобок ( и ) не равно) - вдруг этот глюк исправят в последующих версиях SQLite,
    //тогда удаление будет ненужным и вредным
    $lastel = end($fields);
    if (substr_count($lastel,'(') != substr_count($lastel,')')) {
      $lastel = trim($lastel,')');
      $fields[count($fields)-1] = $lastel;
    }

    foreach ($fields as $v)    {
      // получим тип
      $p = strpos($v, ")"); // для int(10) и пр. вариантов с размерами

      if (!$p) {
        // тип datetime или какой-либо другой без указания размера
        $arr = preg_split("/\s/", $v, -1, PREG_SPLIT_NO_EMPTY);
      } else {
        $f = substr($v, 0, $p + 1);
        $arr = preg_split("/\s/", $f, 2, PREG_SPLIT_NO_EMPTY);
      }

      $name = $arr[0];
      $name = str_replace('`', '', $name);

      $c = array();
      $c['type'] = $arr[1];
      $c['required'] = false;
      $c['key'] = false;
      $c['default'] = null;
      $c['autoincrement'] = false;

      $v =  substr($v,$p+1);

      // проверим на NOT NULL
      if (preg_match("/NOT\s+NULL/i", $v))
        $c['required'] = true;

      // найдём дефолтное значение
      if (preg_match("/DEFAULT\s+(\S+)\s*/i", $v, $matches))
        $c['default'] = str_replace('\'', '', $matches[1]);

      // определим, является ли это первичным ключём или нет
      if (preg_match("/primary/i", $v)) {
        $c['key'] = 'pri';
        $c['autoincrement'] = true;
      }

      if ($indexes[$name])
        $c['key'] = 'mul';

      $columns[$name] = $c;
    }

    return $columns;
  }

  public function addColumn($tblname, $columnName, $column)
  {
    list($sql, $ix) = $this->addSql($columnName, $column, false, false);
    $alter[] = $sql;

    if (null !== ($sql = $this->getSql($tblname, $alter, false))) {
      $this->exec($sql);
    }

    if (!empty($ix)) {
      $sql = "CREATE INDEX `IDX_{$tblname}_{$columnName}` on `{$tblname}` (`{$columnName}`)";
    }
  }

  public function recreateTable($tblname,  $columns, $oldcolumns)
  {
    // В SQLite для удаление полей из таблицы или модификация их типа возможна только
    // путём пересоздания таблицы
    $n = rand(1000, 100000);
    $sql = "ALTER TABLE `{$tblname}` RENAME TO `{$tblname}_old{$n}`";

    $this->exec($sql);

    // создаём новую таблицу из тех полей, которые остались
    $index = $alter = array();
    $isnew = true;
    $oldcolumnstr = $columnstr = "";

    foreach ($columns as $name => $c) {
      list($sql, $ix) = $this->addSql($name, $c, false, $isnew);

      $alter[] = $sql;
      $index[] = $ix;

      if (array_key_exists($name, $oldcolumns)) { //вставке подлежат только те поля, которые уже были в таблице
        $columnstr .= "`{$name}`,";
      }

      if ($c['key']) {
        $sql = "DROP INDEX IF EXISTS `IDX_{$tblname}_{$name}`";
        $this->exec($sql);
      }
    }

    if (null !== ($sql = $this->getSql($tblname, $alter, $isnew))) {
      $this->exec($sql);
    }

    //Создаём индексы
    foreach ($columns as $name => $c) {
      if (($c['key']) && ($c['key'] != 'pri')) {
        $sql = "CREATE INDEX `IDX_{$tblname}_{$name}` on `{$tblname}` (`{$name}`)";
        $this->exec($sql);
      }
    }

    $columnstr = substr($columnstr, 0, -1);

    // Заполняем новую таблицу значениями из старой
    $sql = "INSERT INTO `{$tblname}` ({$columnstr}) SELECT {$columnstr} FROM `{$tblname}_old{$n}`";
    $this->exec($sql);

    // удаляем старую таблицу
    $this->exec("DROP TABLE `{$tblname}_old{$n}`");
  }

  public function addSql($name,  array $spec, $modify, $isnew)
  {
    $sql = '';
    $index = '';

    if (!$isnew) {
      if ($modify)
        //$sql .= "MODIFY COLUMN "; //Вся модификация происходит в recreateTable
        return array('', '');//SQLite не поддерживает MODIFY COLUMN
      else
        $sql .= "ADD COLUMN ";
    }

    $sql .= "`{$name}` ";
    $sql .= $spec['type'];

    if ($spec['required'])
      $sql .= ' NOT NULL';
    else
      $sql .= ' NULL';

    if (null !== $spec['default'])
      $sql .= ' DEFAULT \''. sqlite_escape_string($spec['default']).'\'';

    if ('pri' == $spec['key']) {
      if (!$modify)
        $sql .= ' PRIMARY KEY';
    } elseif (!empty($spec['key'])) {
      $index = $name;
    }

    return array($sql, $index);
   }

   public function getSql($name, array $alter, $isnew)
   {
     if (empty($alter) or empty($alter[0])) return null;
     if ($isnew)
       $sql = "CREATE TABLE `{$name}` (";
     else
       $sql = "ALTER TABLE `{$name}` ";

     $sql .= join(', ', $alter);

     if ($isnew)
       $sql .= ') ';

     return $sql;
  }
}
