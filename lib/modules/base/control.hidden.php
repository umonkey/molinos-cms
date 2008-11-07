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

  public function getHTML($data)
  {
    if (null === $node or (null === ($value = $node->{$this->value})))
      $value = $this->default;

    return mcms::html('input', array(
      'type' => 'hidden',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => $value,
      ));
  }
};
