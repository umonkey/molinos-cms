<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iFormControl
{
  public static function getInfo();
  public static function getSQL();
  public function getHTML(array $data);
  public function validate(array $data);
  public function addControl(Control $ctl);
  public function findControl($value);
};

interface iFormObject
{
  public function formGet();
  public function formGetData();
  public function formProcess(array $data);
};

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
          bebop_debug("Missing {$f} field in control description.", $form, $required_fields);

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
    bebop_debug("Missing getHTML() handler in ". get_class($this) ."!", $this, $data);
    return null;
  }

  protected static function makeHTML($name, array $parts, $content = null)
  {
    $output = '<'. $name;

    foreach ($parts as $k => $v) {
      if (!empty($v)) {
        if (is_array($v))
          if ($k == 'class')
            $v = join(' ', $v);
          else {
            // bebop_debug("Trying to assign this to <{$name} {$k}= />", $v, $parts, $content);
            // throw new InvalidArgumentException(t("Свойство {$k} элемента HTML {$name} не может быть массивом."));
            $v = null;
          }

        $output .= ' '.$k.'=\''. mcms_plain($v, false) .'\'';
      } elseif ($k == 'value') {
        $output .= " value=''";
      }
    }

    if (null === $content and $name != 'a' and $name != 'script' and $name != 'div' and $name != 'textarea') {
      $output .= ' />';
    } else {
      $output .= '>'. $content .'</'. $name .'>';
    }

    return $output;
  }

  protected function getHidden(array $data)
  {
    return self::makeHTML('input', array(
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
    $class = mcms_ctlname($ctl['type']);

    if (class_exists($class))
      return new $class($ctl);

    bebop_debug("Missing control class: {$class}", $ctl);
  }

  public function addControl(Control $ctl)
  {
    $this->children[] = $ctl;
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

  protected function getChildrenHTML(array $data)
  {
    $output = '';

    if ($this instanceof Form)
      $output .= $this->getTabsHTML($data);
    else {
      foreach ($this->children as $child)
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
        ? self::makeHTML('span', array('class' => 'required-label'), '*')
        : '';

      if (substr($label = $this->label, -3) != '...')
        $label .= ':';

      $output = self::makeHTML('label', array(
        'for' => $this->id,
        'class' => $this->required ? 'required' : null,
        ), $label . $star) . $output;
    }

    if (isset($this->description)) {
      $output .= self::makeHTML('div', array(
        'class' => 'note',
        ), $this->description);
    }

    $classes = array(
      'control',
      'control-'. get_class($this) .'-wrapper',
      'control-Type'. substr(get_class($this), 0, -7) .'-wrapper',
      );

    if (in_array('hidden', (array)$this->class))
      $classes[] = 'hidden';

    return self::makeHTML('div', array(
      'id' => $this->wrapper_id,
      'class' => $classes,
      ), $output);
  }

  private function getTabsHTML(array $data)
  {
    $header = $body = '';
    $classes = array('tab-active');

    $count = 0;

    foreach ($this->children as $child) {
      if ($child instanceof FieldSetControl) {
        $tmp = $child->getChildrenHTML($data);

        if (!empty($tmp)) {
          $link = self::makeHTML('a', array(
            'id' => 'tab-'. $child->name,
            'href' => "javascript:bebop_show_tab(\"{$child->name}\");",
            ), $child->label);

          $header .= self::makeHTML('li', array(
            'class' => array_merge($classes, array('tab')),
            ), $link);

          $body .= self::makeHTML('div', array(
            'id' => 'tab-'. $child->name .'-content',
            'class' => array_merge($classes, array('tab-content'))
            ), $child->getChildrenHTML($data));

          $classes = array();

          $count++;
        }
      }
    }

    if (empty($body))
      return '';

    if ($count == 1)
      return $body;

    $output = self::makeHTML('ul', array('class' => 'tabs-header'), $header);
    $output .= $body;

    $output = self::makeHTML('div', array('class' => 'tabs'), $output);

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
};

class SubmitControl extends Control
{
  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public static function getInfo()
  {
    return array(
      'name' => t('Кнопка отправки формы'),
      'hidden' => true,
      );
  }

  public function getHTML(array $data)
  {
    return $this->wrapHTML(parent::makeHTML('input', array(
      'type' => 'submit',
      'id' => $this->id,
      'class' => array('TypeSubmit', 'form-submit'),
      'name' => $this->name,
      'value' => null !== $this->text ? $this->text : t('Сохранить'),
      'title' => $this->title,
      )), false);
  }
};

class ResetControl extends Control
{
  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public static function getInfo()
  {
    return array(
      'name' => t('Кнопка очистки формы'),
      'hidden' => true,
      );
  }

  public function getHTML(array $data)
  {
    return parent::makeHTML('input', array(
      'type' => 'reset',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->name,
      'value' => isset($this->text) ? $this->text : t('Очистить'),
      'title' => $this->title,
      ));
  }
};

class HiddenControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Скрытый элемент'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    return parent::makeHTML('input', array(
      'type' => 'hidden',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => (isset($this->value) and array_key_exists($this->value, $data) and !is_array($data[$this->value])) ? $data[$this->value] : null,
      ));
  }
};

class TextAreaControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текст без форматирования'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['rows']))
      $form['rows'] = 5;

    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    if (null === $this->value or empty($data[$this->value]))
      $content = null;
    else {
      if (is_array($content = $data[$this->value]))
        $content = join("\n", $content);
    }

    $output = parent::makeHTML('textarea', array(
      'id' => $this->id,
      'class' => 'form-text resizable',
      'name' => $this->value,
      'rows' => $this->rows,
      ), $content);

    return $this->wrapHTML($output);
  }
};

class TextHTMLControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текст с форматированием'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $content = (isset($this->value) and !empty($data[$this->value])) ? htmlspecialchars($data[$this->value]) : null;

    $output = parent::makeHTML('textarea', array(
      'id' => $this->id,
      'class' => 'form-text mceEditor',
      'name' => $this->value,
      ), $content);

    return $this->wrapHTML($output);
  }
};

class TextLineControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текстовая строка'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (null === $this->class)
      $this->class = 'form-text';
    else
      $this->class = array_merge(array('form-text'), (array)$this->class);

    $output = parent::makeHTML('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      'readonly' => $this->readonly ? 'readonly' : null,
      ));

    return $this->wrapHTML($output);
  }
};

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
    parent::__construct($form, array('label', 'value'));
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    $output = self::makeHTML('input', array(
      'type' => 'checkbox',
      'name' => $this->value,
      'value' => $this->value ? 1 : $this->value,
      'checked' => empty($data[$this->value]) ? null : 'checked',
      'disabled' => $this->disabled ? 'disabled' : null,
      ));

    $output = self::makeHTML('label', array(
      'id' => $this->id,
      ), $output . $this->label);

    return $this->wrapHTML($output, false);
  }

  public static function getSQL()
  {
    return 'tinyint(1)';
  }
};

class DateControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t("Дата"),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = parent::makeHTML('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => 'form-text',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'DATE';
  }
};

class DateTimeControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Дата и время'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = '';

    if ($this->text)
      $output .= parent::makeHTML('label', array(
        'for' => $this->id,
        ), $this->text);

    $output .= parent::makeHTML('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => array('form-text', 'form-date'),
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'DATETIME';
  }
};

class EmailControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => 'Адрес электронной почты',
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = parent::makeHTML('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => 'form-text',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }
};

class URLControl extends EmailControl
{
  public static function getInfo()
  {
    return array(
      'name' => 'Адрес страницы или сайта',
      );
  }
};

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
      $inner = parent::makeHTML('input', array(
        'type' => 'checkbox',
        'value' => $k,
        'name' => isset($this->value) ? $this->value .'[]' : null,
        'checked' => !empty($data[$this->value]) and in_array($k, $data[$this->value]),
        ));
      $content .= parent::makeHTML('label', array('class' => 'normal checkbox'), $inner . $v);
    }

    return $this->wrapHTML($content);
  }
};

class TableControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Таблица'),
      'hidden' => true,
      );
  }
};

class AttachmentControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Файл'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));

    $this->description .= '<p>'. t("Максимальный размер файла: %size.", array('%size' => ini_get('upload_max_filesize'))) .'</p>';
  }

  public function getHTML(array $data)
  {
    $data = empty($data[$this->value]) ? array() : $data[$this->value];

    $output = self::makeHTML('input', array(
      'type' => 'file',
      'name' => $this->value,
      'id' => $this->id .'-input'
      ));
    $output .= self::makeHTML('input', array(
      'type' => 'hidden',
      'name' => $this->value .'[id]',
      'value' => empty($data['id']) ? null : $data['id'],
      'id' => $this->id .'-hidden',
      ));

    return $this->wrapHTML($output);
  }
};

class FieldSetControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Набор вкладок'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('label'));
  }

  public function getHTML(array $data)
  {
    $output = '';

    $output .= self::makeHTML('legend', array(), $this->label);
    $output .= self::getChildrenHTML($data);

    return parent::makeHTML('fieldset', array(), $output);
  }

  protected function getChildrenHTML(array $data)
  {
    $output = '';

    if (null != $this->intro)
      $output .= '<div class=\'intro\'>'. $this->intro .'</div>';

    return $output . parent::getChildrenHTML($data);
  }
};

class Form extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Форма'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form);
  }

  public function getHTML(array $data)
  {
    $output = '';

    if (isset($this->title)) {
      if (!in_array($header = $this->header, array('h2', 'h3', 'h4', 'h5')))
        $header = 'h2';
      $output = "<{$header} class='form-header'>". mcms_plain($this->title) ."</{$header}>";
    }

    if (null != $this->intro)
      $output .= '<div class=\'intro\'>'. $this->intro .'</div>';

    $output .= parent::makeHTML('form', array(
      'method' => isset($this->method) ? $this->method : 'post',
      'action' => isset($this->action) ? $this->action : $_SERVER['REQUEST_URI'],
      'id' => $this->id,
      'class' => $this->class,
      'enctype' => 'multipart/form-data',
      ), parent::getChildrenHTML($data));

    return $output;
  }
};

class NumberControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Число (целое)'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    $output = parent::makeHTML('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => 'form-text',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? null : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }
};

class FloatControl extends NumberControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Число (дробное)'),
      );
  }
};

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

    if (empty($this->options) and !empty($data[$key = $this->value .':options']) and is_array($data[$key]))
      $this->options = $data[$key];

    // Если поле необязательно или дефолтного значения нет в списке допустимых -- добавляем пустое значение в начало.
    if (!empty($this->options) and (!isset($this->required) or (isset($this->default) and !array_key_exists($this->default, $this->options)))) {
      $options .= self::makeHTML('option', array(
        'value' => '',
        ), $this->default);
    }

    if (empty($data[$this->value]))
      $current = (empty($this->options) or !array_key_exists($this->default, $this->options)) ? null : $this->default;
    else
      $current = $data[$this->value];

    if (is_array($this->options))
      foreach ($this->options as $k => $v) {
        $options .= self::makeHTML('option', array(
          'value' => $k,
          'selected' => ($current == $k) ? 'selected' : null,
          ), $v);
      }

    if (empty($options))
      return '';

    $output = parent::makeHTML('select', array(
      'id' => $this->id,
      'name' => $this->value,
      ), $options);

    return $this->wrapHTML($output);
  }
};

class PasswordControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Пароль'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML(array $data)
  {
    $output = parent::makeHTML('input', array(
      'type' => 'password',
      'id' => $this->id,
      'class' => 'form-text',
      'name' => $this->value,
      'value' => null,
      ));

    return $this->wrapHTML($output);
  }
};

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
        $option = self::makeHTML('input', array(
          'type' => 'radio',
          'class' => 'form-radio',
          'name' => $this->value,
          'checked' => ($selected == $k) ? 'checked' : null,
          'value' => $k,
          ));
        $options .= self::makeHTML('label', array(), $option . $v);
      }

    if (empty($options))
      return '';

    if (isset($this->label))
      $caption = self::makeHTML('legend', array(), $this->label);
    else
      $caption = null;

    return $this->wrapHTML(self::makeHTML('fieldset', array(), $caption . $options), false);
  }
};

class InfoControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текстовое сообщение'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public function getHTML(array $data)
  {
    return isset($this->text)
      ? self::makeHTML('div', array('class' => 'intro'), $this->text)
      : null;
  }
};

class PagerControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Переключатель страниц'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'widget'));
  }

  public function getHTML(array $data)
  {
    if (empty($data[$this->value]))
      return null;

    $pager = $this->getPagerData($data[$this->value]);

    $left = $right = '';

    if ((null !== $pager) and ($pager['pages'] > 1)) {
      if (!empty($pager['prev']))
        $left .= "<a href='{$pager['prev']}'>&larr;</a>";

      foreach ($pager['list'] as $idx => $link) {
        if (!empty($link))
          $left .= "<a href='{$link}'>{$idx}</a>";
        else
          $left .= "<a class='current'>{$idx}</a>";
      }

      if (!empty($pager['next']))
        $left .= "<a href='{$pager['next']}'>&rarr;</a>";
    } elseif (null !== $this->showempty) {
      $left = t('Все документы поместились на одну страницу.');
    }

    foreach (array(10, 30, 60) as $x) {
      $url = bebop_split_url();
      $url['args'][$this->widget]['limit'] = $x;
      $url['args'][$this->widget]['page'] = null;

      $right .= self::makeHTML('a', array('href' => bebop_combine_url($url, false)), $x);
    }

    $output = self::makeHTML('div', array('class' => 'pager_left'), $left);
    $output .= self::makeHTML('div', array('class' => 'pager_right'), $right .'<p>'. t('пунктов на странице') .'</p>');

    return self::makeHTML('div', array('class' => 'pager'), $output);
  }

  private function getPagerData(array $input)
  {
    if (empty($input['limit']))
      return null;

    $output = array();
    $output['documents'] = intval($input['total']);
    $output['perpage'] = intval($input['limit']);
    $output['pages'] = ceil($output['documents'] / $output['perpage']);
    $output['current'] = $input['page'];

    if ($output['current'] == 'last')
      $output['current'] = $output['pages'];

    if ($output['pages'] > 0) {
      if ($output['current'] > $output['pages'] or $output['current'] <= 0)
        throw new PageNotFoundException();

      // С какой страницы начинаем список?
      $beg = max(1, $output['current'] - 5);
      // На какой заканчиваем?
      $end = min($output['pages'], $output['current'] + 5);

      // Расщеплённый текущий урл.
      $url = bebop_split_url();

      for ($i = $beg; $i <= $end; $i++) {
        $url['args'][$this->widget]['page'] = ($i == 1) ? null : $i;
        $output['list'][$i] = ($i == $output['current']) ? null : bebop_combine_url($url);
      }

      if (!empty($output['list'][$output['current'] - 1]))
        $output['prev'] = $output['list'][$output['current'] - 1];
      if (!empty($output['list'][$output['current'] + 1]))
        $output['next'] = $output['list'][$output['current'] + 1];
    }

    return $output;
  }
};

class ActionsControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список действий'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'options'));
  }

  public function getHTML(array $data)
  {
    $options = '';

    foreach ($this->options as $k => $v)
      $options .= self::makeHTML('option', array(
        'value' => $k,
        ), mcms_plain($v));

    $output = self::makeHTML('select', array(
      'name' => $this->value,
      ), $options);

    $output .= self::makeHTML('input', array(
      'type' => 'submit',
      'value' => isset($this->text) ? $this->text : t('OK'),
      ));

    return $this->wrapHTML($output, false);
  }
};
