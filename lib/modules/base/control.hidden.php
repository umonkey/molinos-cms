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
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    if (null === ($value = $data->{$this->value}))
      $value = $this->default;

    return parent::wrapXML(array(
      'type' => 'hidden',
      ), html::cdata($value));
  }
};
