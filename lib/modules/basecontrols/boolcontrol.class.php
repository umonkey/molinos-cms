<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BoolControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Флаг'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    $output = mcms::html('input', array(
      'type' => 'checkbox',
      'name' => $this->value,
      'value' => $this->value ? 1 : $this->value,
      'checked' => empty($data[$this->value]) ? null : 'checked',
      'disabled' => $this->disabled ? 'disabled' : null,
      ));

    if (isset($this->label))
      $output = mcms::html('label', array(
        'id' => $this->id,
        ), $output . $this->label);

    return $this->wrapHTML($output, false);
  }

  public static function getSQL()
  {
    return 'tinyint(1)';
  }
};
