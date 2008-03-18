<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ActionsControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список действий'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'options'));
  }

  public function getHTML(array $data)
  {
    $options = '';

    foreach ($this->options as $k => $v)
      $options .= mcms::html('option', array(
        'value' => $k,
        ), mcms_plain($v));

    $output = mcms::html('select', array(
      'name' => $this->value,
      ), $options);

    $output .= mcms::html('input', array(
      'type' => 'submit',
      'value' => isset($this->text) ? $this->text : t('OK'),
      ));

    return $this->wrapHTML($output, false);
  }
};
