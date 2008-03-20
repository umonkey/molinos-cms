<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FieldControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Поле типа данных (редактор)'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    if (null === $this->name or empty($data[$this->value][$this->name]))
      $data = array(
        'label' => t('Новое поле'),
        );
    else
      $data = $data[$this->value][$this->name];

    $name = (null === $this->name) ? 'new-field' : $this->name;

    $rows = array();

    $rows['Заголовок'] = mcms::html('input', array(
      'type' => 'text',
      'class' => array('form-text', 'form-title'),
      'value' => empty($data['label']) ? null : $data['label'],
      'name' => "{$this->value}[{$name}][label]",
      ));
    $rows['Имя'] = (null === $this->name) ? mcms::html('input', array(
      'type' => 'text',
      'class' => 'form-text',
      'name' => "{$this->value}[{$name}][name]",
      'value' => empty($data['name']) ? null : $data['name'],
      )) : null;
    $rows['Тип'] = mcms::html('select', array(
      'name' => "{$this->value}[{$name}][type]",
      ), $this->getTypes(empty($data['type']) ? null : $data['type']));
    $rows['Описание'] = mcms::html('textarea', array(
      'name' => "{$this->value}[{$name}][description]",
      'class' => 'form-text resizable',
      ), empty($data['description']) ? null : mcms_plain($data['description'])) ."<p class='note'>". t('Выводится под полем для ввода, содержит подсказку для пользователя.') ."</p>";
    $rows['По&nbsp;умолчанию'] = mcms::html('input', array(
      'type' => 'text',
      'class' => 'form-text',
      'name' => "{$this->value}[{$name}][default]",
      'value' => array_key_exists('default', $data) ? $data['default'] : null,
      ));
    $rows['Значения'] = mcms::html('textarea', array(
      'name' => "{$this->value}[{$name}][values]",
      'class' => 'form-text resizable',
      ), empty($data['values']) ? null : mcms_plain($data['values'])) ."<p class='note'>". t('Используется списками значений и набором флагов.') ."</p>";
    $rows['Обязательное'] = mcms::html('input', array(
      'type' => 'checkbox',
      'name' => "{$this->value}[{$name}][required]",
      'value' => 1,
      'checked' => !empty($data['required'])
      ));
    $rows['Индекс'] = mcms::html('input', array(
      'type' => 'checkbox',
      'name' => "{$this->value}[{$name}][indexed]",
      'value' => 1,
      'checked' => !empty($data['indexed'])
      ));
    $rows['Удалить'] = (null !== $this->name) ? mcms::html('input', array(
      'type' => 'checkbox',
      'name' => "{$this->value}[{$name}][delete]",
      'value' => 1,
      )) : null;

    if (null === ($n1 = $n2 = $this->name)) {
      $n1 = 'new-field';
      $n2 = 'Добавить поле';
    }

    $output = "<div id='field-{$n1}-editor'>"
      ."<span class='caption'><a class='selector' href='". mcms_plain($_SERVER['REQUEST_URI']) ."#{$n1}'>{$n2}</a></span>"
      ."<table class='hidden'>";

    foreach ($rows as $title => $content)
      if (null !== $content)
        $output .= "<tr><td class='right'>{$title}:</td><td>{$content}</td></tr>";

    $output .= "</table></div>";

    return $this->wrapHTML($output, false);
  }

  private function getTypes($current = null)
  {
    $types = $output = array();

    if (null !== $current)
      $current = mcms_ctlname($current);

    foreach (mcms::getImplementors('iFormControl') as $class) {
      if (mcms::class_exists($class)) {
        $info = call_user_func(array($class, 'getInfo'));
        if (empty($info['hidden']))
          $types[$class] = $info['name'];
      }
    }

    asort($types);

    foreach ($types as $k => $v)
      $output[] = mcms::html('option', array(
        'value' => $k,
        'selected' => (strtolower($k) == strtolower($current)) ? 'selected' : null,
        ), $v);

    return join('', $output);
  }
};
