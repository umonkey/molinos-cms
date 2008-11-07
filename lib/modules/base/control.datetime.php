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

  public function getHTML($data)
  {
    $output = '';

    if ($this->text)
      $output .= mcms::html('label', array(
        'for' => $this->id,
        ), $this->text);

    $output .= mcms::html('input', array(
      'type' => 'text', // 'datetime', // пользоваться этим в опере невозможно
      'id' => $this->id,
      'class' => 'form-text form-datetime',
      'name' => $this->value,
      'value' => $data->{$this->value},
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'DATETIME';
  }

  public function set($value, Node &$node)
  {
    $this->validate($value);

    if (false === ($value = strtotime($value)))
      throw new RuntimeException(t('Неверный формат даты: %bad, требуется: %good', array(
        '%bad' => $value,
        '%good' => 'ГГГГ-ММ-ДД ЧЧ:ММ:СС',
        )));

    $node->{$this->value} = $value;
  }
};
