<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class EnumRadioControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Выбор из списка (радио)'),
      );
  }

  public function __construct(array $form)
  {
    parent::makeOptionsFromValues($form);
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML(array $data)
  {
    $selected = empty($data[$this->value]) ? null : $data[$this->value];

    if (null === $selected) {
      if (null !== $this->default and array_key_exists($this->default, $this->options))
        $selected = $this->default;
      else {
        $tmp = array_keys($this->options);
        $selected = $tmp[0];
      }
    }

    $options = '';

    if (is_array($this->options))
      foreach ($this->options as $k => $v) {
        $option = mcms::html('input', array(
          'type' => 'radio',
          'class' => 'form-radio',
          'name' => $this->value,
          'checked' => ($selected == $k) ? 'checked' : null,
          'value' => $k,
          ));
        $options .= mcms::html('label', array('class' => 'radio'), $option .'<span>'. $v .'</span>');
      }

    if (empty($options))
      return '';

    if (isset($this->label))
      $caption = mcms::html('legend', array('class' => 'radio'),
        mcms::html('span', $this->label));
    else
      $caption = null;

    return $this->wrapHTML(mcms::html('fieldset', $caption . $options), false);
  }
};
