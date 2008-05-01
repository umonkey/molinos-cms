<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ReadOnlyDatabaseException extends ForbiddenException
{
  public function __construct()
  {
    parent::__construct(t('Вы попытались изменить содержимое базы данных, доступной только для чтения.'));
  }
};
