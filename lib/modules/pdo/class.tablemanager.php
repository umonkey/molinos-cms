<?php

class TableManager implements iSchemaManager
{
  public static function create($params)
  {
     if (array_key_exists('tblname', $params))
       $table_name = $params['tblname'];
     else
       mcms::fatal(t('В TableManager::create не передано имя таблицы.'));

     $classname = 'DBSchema_'. $table_name;
     if (class_exists($classname)) {
        $tbl = new $classname;
        $tbl->create();
     }
     else {
        //throw new TableNotFoundException($table_name, '', null);
        mcms::fatal("Отсутствует класс $classname");
     }
  }
}
