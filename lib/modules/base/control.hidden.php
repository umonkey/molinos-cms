<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
