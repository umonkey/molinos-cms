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
    $this->addClass('form-text');
    $this->addClass('form-date');

    return parent::wrapXML(array(
      'value' => $data->{$this->value},
      ));
  }

  public static function getSQL()
  {
    return 'DATE';
  }
};
