<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FloatControl extends NumberControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Число (дробное)'),
      );
  }
};
