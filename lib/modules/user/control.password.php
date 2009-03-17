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

  public function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getXML($data)
  {
    $this->addClass('form-text');
    $this->addClass('form-password');

    return parent::wrapXML(array());
  }

  public function set($value, Node &$node)
  {
    if (empty($value))
      return;

    if (!is_array($value) or count($value) != 2)
      throw new InvalidArgumentException(t('Значение для PasswordControl должно быть массивом из двух элементов.'));

    $value = array_values($value);

    if (empty($value[0]) and empty($value[1]))
      return;

    if ($value[0] != $value[1])
      throw new ValidationException($this->label, t('Введённые пароли не идентичны.'));

    $node->{$this->value} = $value[0];
  }
};
