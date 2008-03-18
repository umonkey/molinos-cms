<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class EnumControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Выбор из списка (выпадающего)'),
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
    $options = '';

    // Позволяем передавать набор значений через массив данных.
    if (empty($this->options) and !empty($data[$key = $this->value .':options']) and is_array($data[$key]))
      $this->options = $data[$key];

    // Если поле необязательно или дефолтного значения нет в списке допустимых -- добавляем пустое значение в начало.
    if (!empty($this->options) and (!isset($this->required) or (null !== $this->default and !array_key_exists($this->default, $this->options)))) {
      $options .= mcms::html('option', array(
        'value' => '',
        ), $this->default);
    }

    if (empty($data[$this->value]))
      $current = (empty($this->options) or !array_key_exists($this->default, $this->options)) ? null : $this->default;
    else
      $current = $data[$this->value];

    if (is_array($this->options))
      foreach ($this->options as $k => $v) {
        $options .= mcms::html('option', array(
          'value' => $k,
          'selected' => ($current == $k) ? 'selected' : null,
          ), $v);
      }

    if (empty($options))
      return '';

    $output = mcms::html('select', array(
      'id' => $this->id,
      'name' => $this->value,
      ), $options);

    return $this->wrapHTML($output);
  }
};
