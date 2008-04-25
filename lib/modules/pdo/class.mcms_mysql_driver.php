<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class mcms_mysql_driver extends PDO_Singleton
{
  public function __construct(array $conf)
  {
    $dsn = sprintf('mysql:dbname=%s;host=%s', trim($conf['path'], '/'), $conf['host']);

    parent::__construct($dsn, $conf['user'], $conf['pass']);

    $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, 1);
    $this->exec("SET NAMES utf8");
    $this->exec("SET sql_mode = 'STRICT_TRANS_TABLES'");
  }

  public function exec($sql, array $params = null)
  {
    try {
      $sth = $this->prepare($sql);
      $sth->execute($params);
    } catch (PDOException $e) {
      if ('42S02' == $e->getCode())
        throw new TableNotFoundException($e);
    }

    return $sth;
  }
}
