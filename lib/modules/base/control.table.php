<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TableControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Таблица'),
      'hidden' => true,
      );
  }

  public function getHTML(array $data)
  {
    mcms::debug($this, $data);
  }
};
