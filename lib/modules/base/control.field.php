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

  private function isnull(array $data, $key, $default = null)
  {
    return array_key_exists($key, $data) ? $data[$key] : $default;
  }

  private function addProperty(array $data, $name, $title, $content = 'пример')
  {
    $output = mcms::html('td', array('class' => 'fname'), $title .':');
    $output .= mcms::html('td', array('class' => 'fprops'), $content);

    return mcms::html('tr', array(
      'class' => 'fprop '. $name,
      ), $output);
  }

  public function getHTML(array $data)
  {
    $id = $this->id;

    if (null === $this->name or empty($data[$this->value][$this->name]))
      $data = array('label' => t('Новое поле'));
    else
      $data = $data[$this->value][$this->name];

    $body = $this->addProperty($data, 'name', 'Имя', mcms::html('input', array(
      'type' => 'text',
      'value' => $this->name,
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][name]",
      )));
    $body .= $this->addProperty($data, 'title', 'Заголовок', mcms::html('input', array(
      'type' => 'text',
      'value' => $this->isnull($data, 'label'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][label]",
      )));
    $body .= $this->addProperty($data, 'type', 'Тип', mcms::html('select', array(
      'value' => $this->isnull($data, 'type'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][type]",
      ), $this->getTypes($this->isnull($data, 'type', 'TextLineControl'))));

    $body .= $this->addProperty($data, 'dictionary', 'Справочник', mcms::html('select', array(
      'name' => "{$this->value}[{$id}][dictionary]",
      'class' => 'nextid',
      ), $this->getDictionaries($this->isnull($data, 'dictionary', $this->isnull($data, 'values')))));

    $body .= $this->addProperty($data, 'default', 'По умолчанию', mcms::html('input', array(
      'type' => 'text',
      'value' => $this->isnull($data, 'default'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][default]",
      )));
    $body .= $this->addProperty($data, 'values', 'Значения', mcms::html('input', array(
      'type' => 'text',
      'value' => $this->isnull($data, 'values'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][values]",
      )));
    $body .= $this->addProperty($data, 'required', 'Обязательное', mcms::html('input', array(
      'type' => 'checkbox',
      'checked' => $this->isnull($data, 'required') ? 'checked' : null,
      'name' => "{$this->value}[{$id}][required]",
      'class' => 'nextid',
      'value' => 1,
      )));
    $body .= $this->addProperty($data, 'indexed', 'Индекс', mcms::html('input', array(
      'type' => 'checkbox',
      'checked' => $this->isnull($data, 'indexed') ? 'checked' : null,
      'name' => "{$this->value}[{$id}][indexed]",
      'class' => 'nextid',
      'value' => 1,
      )));
    $body .= $this->addProperty($data, 'delete', 'Удалить', mcms::html('input', array(
      'type' => 'checkbox',
      'name' => "{$this->value}[{$id}][delete]",
      'class' => 'nextid',
      'value' => 1,
      )));

    $classes = 'caption fakelink';

    if (!isset($this->name))
      $classes .= ' addnew';

    $output = mcms::html('span', array('class' => $classes), $this->name ? $this->name : 'добавить...');
    $output .= mcms::html('table', array('class' => 'fprops nojs'), $body);

    return $this->wrapHTML(mcms::html('div', array(
      'id' => $this->name ? "field-{$this->name}-editor" : null,
      'class' => 'fprop '. $this->isnull($data, 'type', 'TypeTextControl'),
      ), $output), false);
  }

  protected function wrapHTML($output)
  {
    static $lock = false;

    if (!$lock) {
      $output .= '<script language=\'javascript\' type=\'text/javascript\' '
        .'src=\'lib/modules/base/control.field.js\'></script>';
      $output .= '<link rel=\'stylesheet\' type=\'text/css\' '
        .'href=\'lib/modules/base/control.field.css\' />';

      $lock = true;
    }

    return $output;
  }

  private function getTypes($current = null)
  {
    $types = $output = array();

    foreach ($tmp = mcms::getImplementors('iFormControl') as $class) {
      if (mcms::class_exists($class)) {
        if ('Control' != $class) {
          $info = call_user_func(array($class, 'getInfo'));
          if (empty($info['hidden']))
            $types[$class] = $info['name'];
        }
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

  // FIXME: переделать так, чтобы отображались только справочники.
  private function getDictionaries($current = null)
  {
    $options = array();

    if ((null !== $current) and (false !== strpos($current, '.')))
      $current = substr($current, 0, strpos($current, '.'));

    foreach (TypeNode::getSchema() as $k => $v)
      $options[$k] = $v['title'];

    asort($options);

    $output = '';

    foreach ($options as $k => $v)
      $output .= mcms::html('option', array(
        'value' => $k,
        'selected' => ($k == $current) ? 'selected' : null,
        ), $v);

    /*
    if ($this->name == 'company')
      mcms::debug($current, $options, $this);
    */

    return $output;
  }
};
