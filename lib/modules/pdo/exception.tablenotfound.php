<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TableNotFoundException extends Exception
{
  private $sql = null;
  private $params = null;
  private $table = null;

  public function __construct($table, $sql = null, $params = null)
  {
    $this->sql = $sql;
    $this->params = $params;
    $this->table = $table;

    switch ($table) {
    case 'node':
    case 'node__rel':
      throw new NotInstalledException('table');
    }

    parent::__construct("Таблица {$table} не найдена.");
  }

  public function getQuery()
  {
    return (($ctx = Context::last()) and $ctx->canDebug())
      ? $this->sql
      : null;
  }

  public function getParams()
  {
    return (($ctx = Context::last()) and $ctx->canDebug())
      ? $this->params
      : null;
  }

  public function getTableName()
  {
    return $this->table;
  }
}
