<?php
/**
 * Контрол для выбора нескольких значений из списка.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для выбора нескольких значений из списка.
 *
 * Значения представляются в виде набора чекбоксов, объединённых в FieldSet.
 *
 * @package mod_base
 * @subpackage Controls
 */
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
    parent::__construct($form, array('value'));
  }

  public function getHTML($data)
  {
    $options = $this->getOptions($data);

    if (empty($options))
      return null;

    $selected = $this->getSelected($data);
    $enabled = $this->getEnabled($data);

    // Если ни одна опция не разрешена — не выводим контрол.
    if ($enabled !== null and empty($enabled))
      return null;

    // Если доступен только один раздел — выводим скрытый контрол.
    if ($enabled !== null and count($enabled) == 1) {
      $content = mcms::html('input', array(
        'type' => 'hidden',
        'name' => $this->value . '[]',
        'value' => array_shift($enabled),
        ));
    } else {
      $content = $this->getLabel();

      foreach ($options as $k => $v) {
        $disabled = ((null !== $enabled) and !in_array($k, $enabled))
          ? true
          : false;

        $inner = mcms::html('input', array(
          'type' => 'checkbox',
          'value' => $k,
          'name' => $this->value . '[]',
          'checked' => in_array($k, $selected),
          'disabled' => $disabled ? 'disabled' : null,
          ));

        $inner = mcms::html('label', array('class' => 'normal'), $inner . $v);

        $content .= mcms::html('div', array(
          'class' => 'form-checkbox' . ($disabled ? ' disabled' : ''),
          ), $inner);
      }
    }

    $content .= mcms::html('input', array(
      'type' => 'hidden',
      'name' => $this->value . '[__reset]',
      'value' => 1,
      ));

    return $this->wrapHTML($content, false);
  }

  protected function makeOptionsFromValues(array &$form)
  {
    if (!empty($form['values']) and !is_array($form['values'])) {
      if (0 === strpos($form['values'], ':')) {
        $nodes = Node::find(array(
          'class' => substr($form['values'], 1),
          'published' => 1,
          '#sort' => 'name',
          ));

        $result = array();

        foreach ($nodes as $node)
          $result[$node->id] = $node->name;

        $form['options'] = $result;
        return;
      }
    }

    parent::makeOptionsFromValues($form);
  }

  protected function getOptions($data)
  {
    if ($this->dictionary)
      $options = Node::getSortedList($this->dictionary, $this->field ? $this->field : 'name');
    else
      $options = $this->options;

    return $options;
  }

  protected function getSelected($data)
  {
    $f = $this->parents
      ? 'linkListParents'
      : 'linkListChildren';

    if (!empty($this->dictionary) and $data instanceof Node)
      return (array)$data->$f($this->dictionary, true);
    return (array)$data->{$this->value};
  }

  protected function getEnabled($data)
  {
    return null;
  }

  public function set($value, Node &$node)
  {
    if (empty($value['__reset']))
      $value = null;

    else {
      unset($value['__reset']);
      $this->validate($value);
    }

    $f = $this->parents
      ? 'linkSetParents'
      : 'linkSetChildren';

    if (!empty($this->dictionary)) {
      $node->$f(array_unique((array)$value), $this->dictionary);
    } else {
      $node->{$this->value} = $value;
    }
  }
};
