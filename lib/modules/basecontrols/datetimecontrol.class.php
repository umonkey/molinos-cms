<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DateTimeControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Дата и время'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = '';

    if ($this->text)
      $output .= mcms::html('label', array(
        'for' => $this->id,
        ), $this->text);

    $output .= mcms::html('input', array(
      'type' => 'text', // 'datetime', // пользоваться этим в опере невозможно
      'id' => $this->id,
      'class' => 'form-text form-datetime',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'DATETIME';
  }
};
