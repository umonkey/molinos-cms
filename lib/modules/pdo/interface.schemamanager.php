<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Интерфейс для создания таблиц базы данных.
interface iSchemaManager
{
  public static function create($params);
};
