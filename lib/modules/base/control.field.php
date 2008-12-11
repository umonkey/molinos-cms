<?php
/**
 * Контрол для редактирования поля типа документа.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для редактирования поля типа документа.
 *
 * Скрытый контрол для внутреннего использования.
 *
 * @package mod_base
 * @subpackage Controls
 */
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
    $output = html::em('td', array('class' => 'fname'), $title .':');
    $output .= html::em('td', array('class' => 'fprops'), $content);

    return html::em('tr', array(
      'class' => 'fprop '. $name,
      ), $output);
  }

  public function getHTML($data)
  {
    $id = $this->id;

    if (!($data instanceof Node))
      throw new InvalidArgumentException(t('FieldControl должен получать ноду.'));

    if (null === $this->name or empty($data->{$this->value}[$this->name]))
      $data = array('label' => t('Новое поле'));
    else
      $data = $data->{$this->value}[$this->name];

    $body = $this->addProperty($data, 'name', 'Имя', html::em('input', array(
      'type' => 'text',
      'value' => $this->name,
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][name]",
      )));
    $body .= $this->addProperty($data, 'title', 'Заголовок', html::em('input', array(
      'type' => 'text',
      'value' => $this->isnull($data, 'label'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][label]",
      )));
    $body .= $this->addProperty($data, 'group', 'Группа', html::em('input', array(
      'type' => 'text',
      'value' => $this->isnull($data, 'group', t('Основные свойства')),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][group]",
      )));
    $body .= $this->addProperty($data, 'description', 'Подсказка', html::em('textarea', array(
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][description]",
      'rows' => 5,
      ), mcms_plain($this->isnull($data, 'description'))));
    $body .= $this->addProperty($data, 'type', 'Тип', html::em('select', array(
      'value' => $this->isnull($data, 'type'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][type]",
      ), $this->getTypes($this->isnull($data, 'type', 'TextLineControl'))));

    $body .= $this->addProperty($data, 'dictionary', 'Справочник', html::em('select', array(
      'name' => "{$this->value}[{$id}][dictionary]",
      'class' => 'nextid',
      ), $this->getDictionaries($this->isnull($data, 'dictionary', $this->isnull($data, 'values')))));

    $body .= $this->addProperty($data, 'default', 'По умолчанию', html::em('input', array(
      'type' => 'text',
      'value' => $this->isnull($data, 'default'),
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][default]",
      )));
    $body .= $this->addProperty($data, 'values', 'Значения', html::em('textarea', array(
      'class' => 'nextid',
      'name' => "{$this->value}[{$id}][values]",
      'rows' => 5,
      ), mcms_plain($this->isnull($data, 'values'))));
    $body .= $this->addProperty($data, 'required', 'Обязательное', html::em('input', array(
      'type' => 'checkbox',
      'checked' => $this->isnull($data, 'required') ? 'checked' : null,
      'name' => "{$this->value}[{$id}][required]",
      'class' => 'nextid',
      'value' => 1,
      )));
    $body .= $this->addProperty($data, 'indexed', 'Индекс', html::em('input', array(
      'type' => 'checkbox',
      'checked' => $this->isnull($data, 'indexed') ? 'checked' : null,
      'name' => "{$this->value}[{$id}][indexed]",
      'class' => 'nextid',
      'value' => 1,
      )));
    $body .= $this->addProperty($data, 'delete', 'Удалить', html::em('input', array(
      'type' => 'checkbox',
      'name' => "{$this->value}[{$id}][delete]",
      'class' => 'nextid',
      'value' => 1,
      )));

    $classes = 'caption fakelink';

    if (!isset($this->name))
      $classes .= ' addnew';

    $output = html::em('span', array('class' => $classes), $this->name ? $this->name : 'добавить...');
    $output .= html::em('table', array('class' => 'fprops nojs'), $body);

    return $this->wrapHTML(html::em('div', array(
      'id' => $this->name ? "field-{$this->name}-editor" : null,
      'class' => 'fprop '. strtolower($this->isnull($data, 'type', 'TypeTextControl')),
      ), $output), false);
  }

  protected function wrapHTML($output)
  {
    mcms::extras('lib/modules/base/control.field.js');
    mcms::extras('lib/modules/base/control.field.css');
    return $output;
  }

  private function getTypes($current = null)
  {
    $types = $output = array();

    foreach ($tmp = mcms::getImplementors('iFormControl') as $class) {
      if (class_exists($class)) {
        if ('control' != $class) {
          $info = call_user_func(array($class, 'getInfo'));
          if (empty($info['hidden']))
            $types[$class] = $info['name'];
        }
      }
    }

    asort($types);

    foreach ($types as $k => $v)
      $output[] = html::em('option', array(
        'value' => $k,
        'selected' => strcasecmp($k, $current) ? null : 'selected',
        ), $v);

    return join('', $output);
  }

  // FIXME: переделать так, чтобы отображались только справочники.
  private function getDictionaries($current = null)
  {
    if ((null !== $current) and (false !== strpos($current, '.')))
      $current = substr($current, 0, strpos($current, '.'));

    $options = Node::getSortedList('type', 'title', 'name');

    $output = '';

    foreach ($options as $k => $v)
      $output .= html::em('option', array(
        'value' => $k,
        'selected' => ($k == $current) ? 'selected' : null,
        ), $v);

    return $output;
  }
};
