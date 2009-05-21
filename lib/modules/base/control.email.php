<?php
/**
 * Контрол для ввода адреса электронной почты.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода адреса электронной почты.
 *
 * @package mod_base
 * @subpackage Controls
 */
class EmailControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => 'Адрес электронной почты',
      );
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
      'type' => 'text',
      'mode' => 'email',
      'value' => $value,
      ));
  }

  public function getSQL()
  {
    return 'VARCHAR(255)';
  }
};
