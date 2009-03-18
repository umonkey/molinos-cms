<?php
/**
 * Безовый класс для элементов форм ("контролов").
 *
 * Реализует методы интерфейса iFormControl, повторяющиеся
 * в большинстве элементов.  Использовать этот класс не обязательно,
 * но он упрощает многие вещи, например — формирование подписи
 * или использование вложенных элементов.
 *
 * @see Control::wrapXML()
 * @see Control::getChildrenXML()
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
 * @see Control::wrapXML()
 * @see Control::getChildrenXML()
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
    if (!empty($form['#nocheck'])) {
      $this->form = array();
      return;
    }

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

  public function getXML($data)
  {
    throw new RuntimeException(t('В классе %class нет метода %method!', array(
      '%class' => get_class($this),
      '%method' => 'getXML()',
      )));
  }

  protected function getHidden(array $data)
  {
    return html::em('control', array(
      'type' => 'hidden',
      'name' => $this->value,
      'value' => array_key_exists($this->value, $data) ? $data[$this->value] : null,
      ));
  }

  public function getSQL()
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

  protected function getChildrenXML($data)
  {
    $output = '';

    foreach ($this->children as $child)
      $output .= $child->getXML($data);

    return $output;
  }

  protected final function wrapXML(array $options, $content = null)
  {
    $class = strtolower(get_class($this));

    if (substr($class, -7) != 'control')
      throw new RuntimeException(t('У классов, реализующих контролы, должен быть суффикс Control.'));

    $defaults = array(
      'id' => $this->id,
      'type' => substr($class, 0, -7),
      'label' => $this->label,
      'title' => $this->title,
      'required' => $this->required ? 'yes' : null,
      'description' => $this->description,
      'class' => $this->class,
      'name' => $this->value,
      'readonly' => $this->readonly ? true : false,
      );

    return html::em('control', array_merge($defaults, $options), $content);
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

  public function set($value, &$node)
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
      throw new ValidationException($this->label ? $this->label : $this->value);

    if (!empty($value) and !empty($this->re))
      if (!preg_match($this->re, $value))
        throw new ValidationException($this->label, t('Вы неверно заполнили поле «%field».', array('%field' => mb_strtolower($this->label))));
  }

  /**
   * Преобразует массив в объект, пригодный для использования формами.
   */
  public static function data(array $data = array())
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

  /**
   * Форматирование значения.
   */
  public function format($value)
  {
    return $value;
  }

  /**
   * Возвращает список известных типов полей.
   */
  public static function getKnownTypes()
  {
    $types = array();

    foreach (Context::last()->registry->enum('ru.molinos.cms.control.enum') as $v)
      $types[$v['class']] = $v['result']['name'];

    asort($types);

    return $types;
  }

  /**
   * Получение дополнительных настроек поля.
   */
  public function getExtraSettings()
  {
    return array();
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

  public function __set($key, $value)
  {
    $this->data[$key] = $value;
  }

  public function dump()
  {
    return $this->data;
  }
}
