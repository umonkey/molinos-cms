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
    return mcms::html('input', array(
      'type' => 'hidden',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => (isset($this->value) and array_key_exists($this->value, $data) and !is_array($data[$this->value])) ? $data[$this->value] : null,
      ));
  }
};
