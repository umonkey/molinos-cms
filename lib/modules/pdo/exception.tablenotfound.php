<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TableNotFoundException extends Exception
{
  private $table = null;

  public function __construct($table)
  {
    $this->table = $table;

    switch ($table) {
    case 'node':
    case 'node__rel':
      throw new NotInstalledException('table');
    }

    parent::__construct("Таблица {$table} не найдена.");
  }

  public function getTableName()
  {
    return $this->table;
  }
}
