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

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML(array $data)
  {
    $output = $this->getLabel();

    $output .= mcms::html('input', array(
      'type' => 'password',
      'id' => $this->id,
      'class' => 'form-text form-password1',
      'name' => $this->value .'[]',
      'value' => null,
      ));

    $output .= mcms::html('input', array(
      'type' => 'password',
      'id' => $this->id,
      'class' => 'form-text form-password2',
      'name' => $this->value .'[]',
      'value' => null,
      ));

    return $this->wrapHTML($output, false);
  }
};
