<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class EmailControl extends Control
{
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

  public function getHTML(array $data)
  {
    if (!empty($data[$this->value]))
      $value = $data[$this->value];
    elseif (isset($this->default))
      $value = $this->default;
    else
      $value = null;

    $output = mcms::html('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => 'form-text',
      'name' => $this->value,
      'value' => $value,
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }
};
