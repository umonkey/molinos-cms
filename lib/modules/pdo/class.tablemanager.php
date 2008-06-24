<?php

class TableManager
{
  public $columns = array();

  public static function create($table_name)
  {
    if (empty($table_name))
      mcms::fatal(t('В TableManager::create не передано имя таблицы.'));

    $table_name = trim($table_name, '`');
    $classname = 'DBSchema_'. $table_name;

    if (class_exists($classname)) {
      $tbl = new $classname;
      $tbl->createTable($table_name);
    } else {
      mcms::fatal("Отсутствует класс {$classname}");
    }
  }

  //Проверка на существование поля, вызывается непосредственно из драйвера БД
  public static function checkColumn($table_name, $columnname)
  {
    $table_name = trim($table_name, '`');
    $classname = 'DBSchema_'. $table_name;

    if (class_exists($classname)) {
      $tbl = new $classname;
      $spec = $tbl->getColumn($columnname);

      if (!empty($spec))
        mcms::db()->addColumn($table_name, $columnname, $spec);

      return $spec;
    }
  }

  //возвращает описание заданного поля
  public function getColumn($name)
  {
    if (array_key_exists($name, $this->columns))
      return $this->columns[$name];
    else
      return null;
  }

  //создаёт таблицу
  public function createTable($table_name)
  {
    $t = new TableInfo($table_name);

    foreach ($this->columns as $name => $spec)
       $t->columnSet($name, $spec);

    $t->commit();
  }
}
