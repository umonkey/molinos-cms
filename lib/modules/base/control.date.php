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

  public function getHTML(array $data)
  {
    $output = mcms::html('input', array(
      'type' => 'text', // 'date', // пользоваться этим в опере невозможно
      'id' => $this->id,
      'class' => 'form-text form-date',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'DATE';
  }
};
