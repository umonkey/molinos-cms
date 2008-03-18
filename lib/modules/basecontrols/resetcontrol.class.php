<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ResetControl extends Control
{
  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public static function getInfo()
  {
    return array(
      'name' => t('Кнопка очистки формы'),
      'hidden' => true,
      );
  }

  public function getHTML(array $data)
  {
    return mcms::html('input', array(
      'type' => 'reset',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->name,
      'value' => isset($this->text) ? $this->text : t('Очистить'),
      'title' => $this->title,
      ));
  }
};
