<?php
/**
 * Контрол для редактирования пароля.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для редактирования пароля.
 *
 * Поле выводится дважды, второй экземпляр — для подтверждения.
 *
 * @package mod_base
 * @subpackage Controls
 */
class PasswordControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Пароль'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    return html::em('input', array(
      'type' => 'password',
      'name' => $this->value,
      'required' => $this->required,
      'title' => $this->label,
      ));
  }

  public function set($value, &$node)
  {
    if (empty($value))
      return;

    if (is_array($value))
      $value = array_shift($value);

    $node->{$this->value} = $value;
  }
};
