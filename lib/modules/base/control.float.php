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
class FloatControl extends NumberControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Число (дробное)'),
      );
  }
};
