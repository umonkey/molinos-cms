<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class mcms_mysql_driver extends PDO_Singleton
{
  protected $dbname = null;

  public function __construct(array $conf)
  {
    $this->dbname = trim($conf['path'], '/');
    $dsn = sprintf('mysql:dbname=%s;host=%s', $this->dbname, $conf['host']);

    parent::__construct($dsn, $conf['user'], $conf['pass']);

    $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, 1);
    $this->exec("SET NAMES utf8");
    $this->exec("SET sql_mode = 'STRICT_TRANS_TABLES'");

    $this->dbtype = 'MySQL';
  }

  public function clearDB()
  {
    $sql = "show tables";
    $rows = $this->getResults($sql);

    foreach ($rows as $k => $el) {
      $nk = "Tables_in_". $this->dbname;

      $this->exec("DROP TABLE IF EXISTS `{$el[$nk]}`");
      $this->commit();
    }
  }

  public function getTableInfo($name)
  {
    $columns = array();

    try {
      $data = mcms::db()->getResults("DESCRIBE `{$name}`");

      foreach ($data as $c) {
        $columns[$c['Field']] = array(
        'type' => $c['Type'],
        'required' => 'NO' == $c['Null'],
        'key' => $c['Key'],
        'default' => $c['Default'],
        'autoincrement' => strstr($c['Extra'], 'auto_increment') !== false,
      );
     }
    } catch (TableNotFoundException $e) {
      return false;
    } catch (NotInstalledException $e) {
      return false;
    }

    return $columns;
  }

  public function exec($sql, array $params = null)
  {
    try {
      $sth = $this->prepare($sql);
      $sth->execute($params);
    } catch (PDOException $e) {
      if ('42S02' == $e->getCode()) { //нет таблицы
        if (preg_match("/Table '([^.]+)\.([^']+)' doesn/", $e->getMessage(), $m))
          $tname = $m[2];
        else
          $tname = null;

        if (stristr($sql, 'DESCRIBE')) {
          // Это MySQL. Проверка существования таблицы идёт через DESCRIBE в getTableInfo()
          // Если её нет, надо выкинуть TableNotFoundException, чтобы TableInfo::exists() вернул false
          throw new TableNotFoundException($tname);
        } else {
          if (preg_match("/node__idx_(\S+)/i", $sql, $tblmatches)) {
            //для индексных таблиц свой механизм пересоздания
            $node = Node::load(array('class' => 'type', 'name' => $tblmatches[1]));
            if (!empty($node)) {
              $node->recreateIdxTable($tblmatches[1]);
              return self::exec($sql, $params);
            }
          } else {
            TableManager::create($tname);
            return self::exec($sql, $params);
          }
        }
      } else if ('42S22' == $e->getCode()) {//нет поля
        if (preg_match("/node__idx_(\S+)/i", $sql, $tblmatches)) {
          //для индексных таблиц тупо занимаемся пересозданием
          $node = Node::load(array('class' => 'type', 'name' => $tblmatches[1]));
          if (!empty($node)) {
            $node->recreateIdxTable($tblmatches[1]);
            $sth = $this->prepare($sql);
            $sth->execute($params);
            return $sth;
          }
        }
        else { //Это не индексная таблица, а одна из основных
          //Получим имя столбца
          if (preg_match("/column\s*'(\S+)'/i",$e->getMessage(), $matches)) {
            $cname = trim($matches[1]);

            $tables = null;
            if (preg_match("/from\s*(.+)(?=where|order|\s*)/i", $sql, $matches))
              $tables = str_replace("`","",$matches[1]);

            if  (preg_match("/(into|update)\s*[`]?([\w_]+)[`]?/i", $sql, $matches))
              $tables = $matches[2];

            if ($tables != null) {
              $tlist = preg_split("/\,\s*/", $tables, -1, PREG_SPLIT_NO_EMPTY);

              foreach ($tlist as $tbl) {
                $spec = TableManager::checkColumn($tbl, $cname);

                if (!empty($spec)) {
                  $sth = $this->prepare($sql);
                  $sth->execute($params);
                  return $sth;
                }
              }
            }
          }
        }
      }

      throw new McmsPDOException($e, $sql);
    }

    return $sth;
  }

  public function dropColumn($tblname, $coldel)
  {
    $sql = "ALTER TABLE `{$tblname}` DROP COLUMN `$coldel`";
    $this->exec($sql);
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

  public function addSql($name, array $spec, $modify, $isnew)
  {
    $sql = '';
    $index = '';

    if (!$isnew) {
      if ($modify)
       $sql .= "MODIFY COLUMN ";
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
      $sql .= ' DEFAULT \''. mysql_escape_string($spec['default']).'\'';

    if ('pri' == $spec['key']) {
      if (!$modify)
        $sql .= ' PRIMARY KEY';

      if ($spec['autoincrement'])
        $sql .= ' AUTO_INCREMENT';
    } elseif (!empty($spec['key'])) {
      $index = $name;
    }

    return array($sql, $index);
  }

  public function getSql($name, array $alter, $isnew)
  {
    if ($isnew)
      $sql = "CREATE TABLE `{$name}` (";
    else
      $sql = "ALTER TABLE `{$name}` ";

    $sql .= join(', ', $alter);

    if ($isnew) {
      $sql .= ') ';
      $sql .= ' CHARSET=utf8';
    }

    return $sql;
  }

  public function hasOrderedUpdates()
  {
    return true;
  }
}
