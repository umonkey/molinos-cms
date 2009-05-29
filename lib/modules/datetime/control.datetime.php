<?php
/**
 * Контрол для ввода даты и времени.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода даты и времени.
 *
 * @package mod_base
 * @subpackage Controls
 */
class DateTimeControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Дата и время'),
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
      'mode' => 'datetime',
      ), html::cdata($data->{$this->value}));
  }

  public function getSQL()
  {
    return 'DATETIME';
  }

  protected function validate($value)
  {
    if (!$this->required and empty($value))
      return true;

    if (false === ($value = strtotime($value)))
      throw new RuntimeException(t('Неверный формат даты: %bad, требуется: %good', array(
        '%bad' => $value,
        '%good' => 'ГГГГ-ММ-ДД ЧЧ:ММ:СС',
        )));
  }
};
