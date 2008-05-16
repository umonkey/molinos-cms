<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NoIndexException extends UserErrorException
{
  public function __construct($name)
  {
    parent::__construct('Отсутствует индекс '. $name, 500, 'Отсутствует индекс', t('Выборка по полю %field невозможна: отсутствует индекс.', array('%field' => $name)));
  }
};
