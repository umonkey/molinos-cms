<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class mcms_sqlite_driver extends PDO_Singleton
{
  private $dbfile = null;

  public function __construct(array $conf)
  {
    $this->dbfile = trim($conf['path'], '/');
    $dsn = 'sqlite:'. $this->dbfile;

    parent::__construct($dsn, '', '');

    $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, 1);

    $this->dbtype = 'SQLite';
  }

  public function exec($sql, array $params = null)
  {
    try {
      $sth = $this->prepare($sql);
      $sth->execute($params);
    } catch (PDOException $e) {
      $info = $this->errorInfo();
      $errorcode = $info[1];

      switch ($errorcode) {
      case 1: // General error: 1 no such table: xyz.
        throw new TableNotFoundException(trim(strrchr($info[2], ' ')), $sql, $params);

      default:
        throw new McmsPDOException($e, $sql);
      }
    }

    return $sth;
  }

  public function clearDB()
  {
    if ((null !== $this->dbfile) and file_exists($this->dbfile) and filesize($this->dbfile) > 0)
      copy($this->dbfile, $this->dbfile .'.'. strftime('%Y%m%d%H%M%S'));

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

    foreach ($fields as $v)    {
      // получим тип
      $p = strpos($v, ")"); // для int(10) и пр. вариантов с размерами

      if (!$p) {
        // тип datetime или какой-либо другой без указания размера
        $arr = preg_split("/\s/", $v, 2, PREG_SPLIT_NO_EMPTY);
      } else {
        $f = substr($v, 0, $p + 1);
        $arr = preg_split("/\s/", $f, 2, PREG_SPLIT_NO_EMPTY);
      }

      $name = $arr[0];
      $name = str_replace('`', '', $name);

      $c = array();
      $c['type'] = $arr[1];
      $c['required'] = false;
      $c['key'] = 0;
      $c['default'] = null;
      $c['autoincrement'] = false;

      if ($p) {
        $v =  substr($v,$p+1);

        // проверим на NOT NULL
        if (preg_match("/NOT\s+NULL/i", $v))
          $c['required'] = true;

        // найдём дефолтное значение
        if (preg_match("/DEFAULT\s+(\S+)\s/i", $v, $matches))
          $c['default'] = str_replace('\'', '', $matches[1]);

        // определим, является ли это первичным ключём или нет
        if (preg_match("/primary/i", $v)) {
          $c['key'] = 'pri';
          $c['autoincrement'] = true;
        }

        if ($indexes[$name])
          $c['key'] = 'mul';
      }

      $columns[$name] = $c;
    }

    return $columns;
  }

  public function dropColumn($tblname,$coldel, $columns)
  {
    // В SQLite удаление полей из таблицы происходит по другому, нежели в mysql.
    $n = rand(1000,100000);
    $sql = "ALTER TABLE `{$tblname}` RENAME TO `{$tblname}_old{$n}`";

    $this->exec($sql);

    // создаём новую таблицу из тех полей, которые остались
    $index = $alter = array();
    $isnew = true;
    $columnstr = "";

    foreach ($columns as $name => $c) {
      list($sql, $ix) = $this->addSql($name, $c, false, $isnew);

      $alter[] = $sql;
      $index[] = $ix;

      // Удалим существующие индексы у старой таблицы
      $columnstr .= "`{$name}`,";

      if ($c['key']) {
        $sql = "DROP INDEX IF EXISTS `IDX_{$tblname}_{$name}`";
        $this->exec($sql);
      }
    }

    if (null !== ($sql = $this->getSql($tblname, $alter, $isnew))) {
      $this->exec($sql);
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

     if ($isnew)
       $sql .= ') ';

     return $sql;
  }
}
