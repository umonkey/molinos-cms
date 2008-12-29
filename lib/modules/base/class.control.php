<?php
/**
 * Безовый класс для элементов форм ("контролов").
 *
 * Реализует методы интерфейса iFormControl, повторяющиеся
 * в большинстве элементов.  Использовать этот класс не обязательно,
 * но он упрощает многие вещи, например — формирование подписи
 * или использование вложенных элементов.
 *
 * @see Control::wrapHTML()
 * @see Control::getChildrenHTML()
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Безовый класс для элементов форм ("контролов").
 *
 * Реализует методы интерфейса iFormControl, повторяющиеся
 * в большинстве элементов.  Использовать этот класс не обязательно,
 * но он упрощает многие вещи, например — формирование подписи
 * или использование вложенных элементов.
 *
 * @see Control::wrapHTML()
 * @see Control::getChildrenHTML()
 *
 * @package mod_base
 * @subpackage Controls
 */
abstract class Control implements iFormControl
{
  /**
   * Массив с описанием контрола.
   */
  private $form;

  /**
   * Массив дочерних элементов.
   *
   * Используется групповыми элементами, например — FieldSetControl.
   * @see FieldSetControl
   */
  private $children;

  /**
   * Уникальный идентификатор контрола.
   */
  private $_id;

  public function __construct(array $form = array(), array $required_fields = null)
  {
    static $lastid = 0;

    if (null !== $required_fields)
      foreach ($required_fields as $f)
        if (!array_key_exists($f, $form)) {
          mcms::debug("Missing \"{$f}\" field in control description.", $form, $required_fields);

          throw new InvalidArgumentException(t("В описании контрола типа %class обязательно должно присутствовать поле %field!", array(
            '%class' => get_class($this),
            '%field' => $f,
            )));
        }

    if (array_key_exists('value', $form))
      $form['value'] = str_replace('.', '_', $form['value']);

    $this->form = $form;
    $this->children = array();

    /*
    if (null === $this->id and null !== $this->value)
      $this->id = 'unnamed-ctl-'. ++$lastid;
    */

    if (empty($this->class))
      $this->class = array();
    elseif (!is_array($this->class))
      $this->class = explode(' ', $this->class);
  }

  protected function __get($key)
  {
    if (array_key_exists($key, $this->form))
      return $this->form[$key];
    elseif ('id' == $key) {
      if (null === $this->_id) {
        static $nextid = 1;
        $this->_id = $nextid++;
      }
      return 'ctl__'. $this->_id;
    } else {
      return null;
    }
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

  public function getHTML($data)
  {
    mcms::debug("Missing getHTML() handler in ". get_class($this) ."!", $this, $data);
    return null;
  }

  protected function render(array $data)
  {
    $class = get_class($this);
    $ctrln = mb_strtolower(substr($class, 0, -7));

    return template::render('admin', 'control', $ctrln, array(
      'd' => $data,
      'c' => $this,
      ), $class);
  }

  protected function getHidden(array $data)
  {
    return html::em('input', array(
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

    if (class_exists($class))
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

  public function hideControl($name)
  {
    $this->replaceControl($name, new HiddenControl(array(
      'value' => $name,
      )));
  }

  public function getChildren()
  {
    return $this->children;
  }

  protected function getChildrenHTML($data)
  {
    $fields = '';
    $hidden = '';
    $other = '';

    foreach ($this->children as $child) {
      $child->captcha = $this->captcha;

      if ($child instanceof FieldSetControl)
        $fields .= $child->getHTML($data);
      elseif ($child instanceof HiddenControl)
        $hidden .= $child->getHTML($data);
      else
        $other .= $child->getHTML($data);
    }

    $output = $fields;

    if (!empty($hidden))
      $output .= html::em('fieldset', array(
        'style' => 'display:none',
        ), $hidden);

    $output .= $other;

    return $output;
  }

  protected function wrapHTML($output, $with_label = true)
  {
    if (!empty($this->nolabel))
      $with_label = false;

    if ($with_label)
      $output = $this->getLabel($output);

    if (isset($this->description)) {
      $output .= html::em('div', array(
        'class' => 'note',
        ), $this->description);
    }

    $classes = array(
      'control',
      $this->getWrapperClass(),
      $this->value . '-field-wrapper',
      );

    if (!empty($this->class))
      $classes = array_merge($classes, $this->class);

    if (in_array('hidden', (array)$this->class))
      $classes[] = 'hidden';

    return html::em('div', array(
      'id' => $this->wrapper_id,
      'class' => $classes,
      ), $output);
  }

  protected function getLabel($output = null)
  {
    if (empty($this->label))
      return $output;

    $star = $this->required
      ? html::em('span', array('class' => 'required-label'), '*')
      : '';

    if (substr($label = $this->label, -3) != '...')
      if (substr($label, -1) != ':')
        $label .= ':';

    $output = html::em('label', array(
      'class' => $this->required ? 'required' : null,
      ), html::em('span', $label . $star) . $output);

    return $output;
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

  public function set($value, Node &$node)
  {
    $this->validate($value);

    if ('' === $value or null === $value)
      unset($node->{$this->value});
    else
      $node->{$this->value} = $value;
  }

  protected function validate($value)
  {
    if ($this->required and empty($value))
      throw new ValidationException($this->label);

    if (!empty($value) and !empty($this->re))
      if (!preg_match($this->re, $value))
        throw new ValidationException($this->label, t('Вы неверно заполнили поле «%field».', array('%field' => mb_strtolower($this->label))));
  }

  /**
   * Преобразует массив в объект, пригодный для использования формами.
   */
  public static function data(array $data)
  {
    return new ControlData($data);
  }

  /**
   * Возвращает идентификатор объекта для сохранения в виде ссылки.
   * Если поле не поддерживает хранение в виде ссылки, нужно вернуть
   * false, что и делает базовый обработчик.  Для очистки ссылки можно
   * вернуть null.
   */
  public function getLinkId($data)
  {
    return false;
  }

  public function getIndexValue($value)
  {
    return $value;
  }

  protected function getWrapperClass()
  {
    return mb_strtolower(str_replace('Control', '', get_class($this))) .'-wrapper';
  }

  /**
   * Возвращает настройки контрола для сохранения в структуре.
   */
  public function dump()
  {
    $result = array();

    foreach ($this->form as $k => $v)
      if (!empty($v))
        $result[$k] = $v;

    return $result;
  }
};

class ControlData
{
  private $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function __get($key)
  {
    return $this->__isset($key)
      ? $this->data[$key]
      : null;
  }

  public function __isset($key)
  {
    return array_key_exists($key, $this->data);
  }
}
