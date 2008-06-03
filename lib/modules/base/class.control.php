<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

abstract class Control implements iFormControl
{
  private $form;
  private $children;

  protected function __construct(array $form, array $required_fields = null)
  {
    static $lastid = 0;

    if (null !== $required_fields)
      foreach ($required_fields as $f)
        if (!array_key_exists($f, $form)) {
          mcms::debug("Missing {$f} field in control description.", $form, $required_fields);

          throw new InvalidArgumentException(t("В описании контрола типа %class обязательно должно присутствовать поле %field!", array(
            '%class' => get_class($this),
            '%field' => $f,
            )));
        }

    if (array_key_exists('value', $form))
      $form['value'] = str_replace('.', '_', $form['value']);

    $this->form = $form;
    $this->children = array();

    if (null === $this->id and null !== $this->value)
      $this->id = 'unnamed-ctl-'. ++$lastid;

    if (empty($this->class))
      $this->class = array();
    elseif (!is_array($this->class))
      $this->class = explode(' ', $this->class);
  }

  protected function __get($key)
  {
    if (array_key_exists($key, $this->form))
      return $this->form[$key];
    else
      return null;
  }

  protected function __set($key, $value)
  {
    $this->form[$key] = $value;
  }

  protected function __isset($key)
  {
    return array_key_exists($key, $this->form) and !empty($this->form[$key]);
  }

  protected function __unset($key)
  {
    if (array_key_exists($key, $this->form))
      unset($this->form[$key]);
  }

  public function addClass($class)
  {
    $this->form['class'][] = $class;
  }

  public function validate(array $data)
  {
    return true;
  }

  public function getHTML(array $data)
  {
    mcms::debug("Missing getHTML() handler in ". get_class($this) ."!", $this, $data);
    return null;
  }

  protected function getHidden(array $data)
  {
    return mcms::html('input', array(
      'type' => 'hidden',
      'name' => $this->value,
      'value' => array_key_exists($this->value, $data) ? $data[$this->value] : null,
      ));
  }

  public static function getSQL()
  {
    return null;
  }

  // Используется для прозрачной миграции со старых версий.
  public static function make(array $ctl)
  {
    $class = $ctl['type'];

    if (mcms::class_exists($class))
      return new $class($ctl);

    mcms::debug("Missing control class: {$class}", $ctl);
  }

  public function addControl(Control $ctl)
  {
    return $this->children[] = $ctl;
  }

  public function findControl($value)
  {
    if (null === $value)
      return null;

    if ($this->value == $value)
      return $this;

    foreach ($this->children as $child) {
      if (null !== ($ctl = &$child->findControl($value)))
        return $ctl;
    }

    return null;
  }

  public function replaceControl($value, Control $ctl = null)
  {
    if (null === $value)
      return false;

    foreach ($this->children as $k => $v) {
      if ($value == $v->value) {
        if (null !== $ctl)
          $this->children[$k] = $ctl;
        else
          unset($this->children[$k]);
        return true;
      } elseif ($v->replaceControl($value, $ctl)) {
        return true;
      }
    }

    return false;
  }

  protected function getChildrenHTML(array $data)
  {
    $output = '';

    foreach ($this->children as $child) {
      $child->captcha = $this->captcha;

      if ($child instanceof FieldSetControl)
        $output .= $child->getHTML($data);
    }

    foreach ($this->children as $child)
      if (!($child instanceof FieldSetControl))
        $output .= $child->getHTML($data);

    return $output;
  }

  protected function wrapHTML($output, $with_label = true)
  {
    if ($with_label and isset($this->label)) {
      $star = $this->required
        ? mcms::html('span', array('class' => 'required-label'), '*')
        : '';

      if (substr($label = $this->label, -3) != '...')
        $label .= ':';

      $output = '<div class=\'label\'>'. mcms::html('label', array(
        'for' => $this->id,
        'class' => $this->required ? 'required' : null,
        ), $label . $star) .'</div>'. $output;
    }

    if (isset($this->description)) {
      $output .= mcms::html('div', array(
        'class' => 'note',
        ), $this->description);
    }

    $classes = array(
      'control',
      'control-'. get_class($this) .'-wrapper',
      );

    if (in_array('hidden', (array)$this->class))
      $classes[] = 'hidden';

    return mcms::html('div', array(
      'id' => $this->wrapper_id,
      'class' => $classes,
      ), $output);
  }

  protected function makeOptionsFromValues(array &$form)
  {
    if (empty($form['options']) and !empty($form['values']) and is_string($form['values'])) {
      $form['options'] = array();

      foreach (explode("\n", $form['values']) as $value) {
        $pair = explode('=', $value, 2);

        if (count($pair) == 2)
          $form['options'][trim($pair[0])] = trim($pair[1]);
        else
          $form['options'][trim($pair[0])] = trim($pair[0]);
      }

      unset($form['values']);
    }
  }
};
