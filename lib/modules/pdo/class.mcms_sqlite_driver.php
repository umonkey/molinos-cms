<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class mcms_sqlite_driver extends Database
{
  private $dbfile = null;

  public function __construct(array $conf)
  {
    $this->dbfile = $this->dbname = MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . $conf['name'];

    if (':memory:' != $this->dbfile and !file_exists($this->dbfile))
      os::copy(os::path('lib', 'modules', 'pdo', 'default.sqlite'), $this->dbfile);

    $dsn = 'sqlite:'. $this->dbfile;

    if (':memory:' != $this->dbfile) {
      if (!file_exists(realpath($this->dbfile)))
        throw new NotInstalledException('db');
    }

    try {
      parent::__construct($dsn, '', '');
    } catch (PDOException $e) {
      if (!in_array('sqlite', PDO::getAvailableDrivers()))
        throw new NotInstalledException('driver');
      elseif (file_exists($conf['name']))
        throw new RuntimeException(t('Не удалось открыть базу данных.'));
      else
        throw new NotInstalledException('connection');
    }

    $this->dbtype = 'SQLite';
  }

  public function exec($sql, array $params = null)
  {
    try {
      return parent::exec($sql, $params);
    } catch (PDOException $e) {
      $info = $this->errorInfo();

      switch ($info[1]) {
      case 8:
        throw new ReadOnlyDatabaseException();

      default:
        throw $e;
      }
    }
  }

  /**
   * Подмена несуществующих функций готовыми значениями.
   */
  protected function rewriteSQL($sql)
  {
    $fixes = array(
      'UTC_TIMESTAMP()' => '\''. gmdate('Y-m-d H:i:s') .'\'',
      'YEAR(' => 'strftime(\'%Y\', ',
      'MONTH(' => 'strftime(\'%m\', ',
      'DAY(' => 'strftime(\'%d\', ',
      'RAND()' => 'RANDOM()',
      );

    return parent::rewriteSQL(str_replace(array_keys($fixes), array_values($fixes), $sql));
  }

  public function prepare($sql, array $options = null)
  {
    try {
      return parent::prepare($sql);
    } catch (PDOException $e) {
      if (false !== ($pos = strpos($e->getMessage(), 'General error: 1 no such table: ')))
        throw new TableNotFoundException(substr($e->getMessage(), $pos + 32));
      throw $e;
    }
  }

  public function clearDB()
  {
  	// Прежде чем все похерить, стоит сделать резервную копию.
  	$this->makeBackup();

    mcms::flog('deleting everything');

    $sql = "SELECT `tbl_name` FROM `sqlite_master` WHERE `type` = 'table' AND `tbl_name` NOT LIKE 'sqlite_%'";
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
    if ((null !== $this->dbfile) and file_exists($this->dbfile) and
        filesize($this->dbfile) > 0) {
      $fname = $this->dbfile .'.'. gmdate('YmdHis');
      mcms::flog('backing up as '. $fname);
      copy($this->dbfile, $fname);
    }
  }

  public function getTableInfo($name)
  {
    $indexes = array();
    $sql = "SELECT * FROM `sqlite_master` WHERE `tbl_name` = '{$name}' AND `type` = 'index'";
    $rows = $this->getResults($sql);

    foreach ($rows as $k => $el) {
      if (null !== ($str = $el['sql'])) {
        if ($col = preg_match("/\((.+)\)/", $str, $matches)) {
          $col = $matches[1];
          $col = str_replace('`', '', $col);
          $indexes[$col] = 1;
        }
      }
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

      if (!empty($indexes[$name]))
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
    $this->beginTransaction();

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

    $this->commit();
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
      $sql .= ' DEFAULT \''. /*FIXME sqlite_escape_string*/($spec['default']).'\'';

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

   public function getDbFile()
   {
     return $this->getDbName();
   }
}
