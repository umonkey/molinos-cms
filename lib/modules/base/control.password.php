<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
    $output = mcms::html('input', array(
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

    return $this->wrapHTML($output);
  }
};
