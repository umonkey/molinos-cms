<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DateControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t("Дата"),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = mcms::html('input', array(
      'type' => 'text', // 'date', // пользоваться этим в опере невозможно
      'id' => $this->id,
      'class' => 'form-text form-date',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'DATE';
  }
};
