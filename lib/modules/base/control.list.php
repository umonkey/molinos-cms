<?php

class ListControl extends TextLineControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Список значений (через запятую)'),
      );
  }

  protected function getValue($data)
  {
    $value = parent::getValue($data);

    if (is_array($value))
      $value = join(', ', $value);

    return $value;
  }

  public function set($value, &$node)
  {
    if (!empty($value))
      $value = preg_split('/,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
    else
      $value = array();

    return parent::set($value, $node);
  }
}
