<?php
/**
 * Контрол для ввода ссылки (URL).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода ссылки (URL).
 *
 * @package mod_base
 * @subpackage Controls
 */
class URLControl extends EmailControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => 'Адрес страницы или сайта',
      );
  }

  public function getSQL()
  {
    return null;
  }
};
