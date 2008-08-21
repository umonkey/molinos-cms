<?php
/**
 * Контрол для ввода дробных чисел.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода дробных чисел.
 *
 * @package mod_base
 * @subpackage Controls
 */
class FloatControl extends NumberControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Число (дробное)'),
      );
  }
};
