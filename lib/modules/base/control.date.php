<?php
/**
 * Контрол для ввода даты (без времени).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода даты (без времени).
 *
 * @package mod_base
 * @subpackage Controls
 */
class DateControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t("Дата"),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    return parent::wrapXML(array(
      'type' => 'text',
      'mode' => 'date',
      'value' => $data->{$this->value},
      ));
  }

  public function getSQL()
  {
    return 'DATE';
  }
};
