<?php

class ListControl extends TextLineControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список значений (через запятую)'),
      );
  }

  public function set($value, Node &$node)
  {
    if (!empty($value))
      $value = preg_split('/,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
    else
      $value = array();

    return parent::set($value, $node);
  }
}
