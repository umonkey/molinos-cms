<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SetControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Флаги (несколько галочек)'),
      );
  }

  public function __construct(array $form)
  {
    parent::makeOptionsFromValues($form);
    parent::__construct($form, array('value', 'options'));
  }

  public function getHTML(array $data)
  {
    if (!isset($this->options))
      return null;

    $values = array();
    $content = '';

    foreach ($this->options as $k => $v) {
      $inner = mcms::html('input', array(
        'type' => 'checkbox',
        'value' => $k,
        'name' => isset($this->value) ? $this->value .'[]' : null,
        'checked' => !empty($data[$this->value]) and in_array($k, $data[$this->value]),
        ));
      $content .= '<div class=\'form-checkbox\'>'. mcms::html('label', array('class' => 'normal'), $inner . $v) .'</div>';
    }

    return $this->wrapHTML($content);
  }
};
