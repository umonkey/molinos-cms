<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ReadOnlyDatabaseException extends RuntimeException
{
  public function __construct()
  {
    parent::__construct(t('База данных закрыта от записи, внесение изменений невозможно.'));
  }
};
