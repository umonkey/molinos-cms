<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeLinkControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Связь с документом'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'int(10) unsigned';
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (empty($data[$this->value]))
      $value = $this->default;
    else
      $value = $data[$this->value];

    $this->addClass('form-text');

    if (!$this->readonly)
      $this->addClass('autocomplete');

    $output = mcms::html('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => $this->class,
      'autocomplete' => 'off',
      'name' => $this->value,
      'value' => $value,
      'readonly' => $this->readonly ? 'readonly' : null,
      ));

    if (!$this->readonly)
      $output .= '<script language=\'javascript\'>$(\'#'. $this->id .'\').suggest(\'/autocomplete.rpc?source='. $this->values .'\');</script>';

    return $this->wrapHTML($output);
  }
};
