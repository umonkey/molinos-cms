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
      if ('42S02' == $e->getCode()) {
        if (preg_match("/Table '([^.]+)\.([^']+)' doesn/", $e->getMessage(), $m))
          $tname = $m[2];
        else
          $tname = null;

        throw new TableNotFoundException($tname);
      }

      throw new McmsPDOException($e, $sql);
    }

    return $sth;
  }

  public function dropColumn($tblname,$coldel, $columns)
  {
     foreach ($coldel as $name => $c) {
        $sql = "ALTER TABLE `{$tblname}` DROP COLUMN `{$name}`";
        $this->exec($sql);
     }
  }

  public function addSql($name,  array $spec, $modify, $isnew)
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
      $sql .= ' DEFAULT '. $spec['default'];

    if ('pri' == $spec['key']) {
      if (!$modify)
        $sql .= ' PRIMARY KEY';

      if ($spec['autoincrement'])
        $sql .= ' AUTO_INCREMENT';
     }
     elseif (!empty($spec['key'])) {
       $index = $name;
     }

     return array($sql,$index);
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

       $conf = array(
         'engine' => 'InnoDB',
         );

       if (!empty($conf['engine']))
         $sql .= ' ENGINE='. $conf['engine'];
     }

     return $sql;
   }
}
