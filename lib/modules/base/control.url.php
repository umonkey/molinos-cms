<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class URLControl extends EmailControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => 'Адрес страницы или сайта',
      );
  }
};
