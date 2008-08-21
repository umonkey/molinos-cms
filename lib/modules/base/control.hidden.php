<?php
/**
 * Скрытый контрол.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Скрытый контрол.
 *
 * Используется для скрытой передачи параметров обработчику формы.
 *
 * @package mod_base
 * @subpackage Controls
 */
class HiddenControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Скрытый элемент'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    if (isset($this->value) and array_key_exists($this->value, $data) and !is_array($data[$this->value]))
      $value = $data[$this->value];
    elseif (isset($this->default))
      $value = $this->default;
    else
      $value = null;

    return mcms::html('input', array(
      'type' => 'hidden',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => $value,
      ));
  }
};
