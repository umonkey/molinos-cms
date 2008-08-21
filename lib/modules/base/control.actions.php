<?php
/**
 * Контрол для выбора действия из группы.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для выбора действия из группы.
 *
 * Используется только в административном интерфейсе (для публикации, удаления
 * итд).
 *
 * @package mod_base
 * @subpackage Controls
 */
class ActionsControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список действий'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'options'));
  }

  public function getHTML(array $data)
  {
    $options = '';

    foreach ($this->options as $k => $v)
      $options .= mcms::html('option', array(
        'value' => $k,
        ), mcms_plain($v));

    $output = mcms::html('select', array(
      'name' => $this->value,
      ), $options);

    $output .= mcms::html('input', array(
      'type' => 'submit',
      'value' => isset($this->text) ? $this->text : t('OK'),
      ));

    return $this->wrapHTML($output, false);
  }
};
