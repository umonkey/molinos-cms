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
    if (!empty($form['values']) and 0 === strpos($form['values'], ':'))
      $form['dictionary'] = substr($form['values'], 1);

    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    $options = $this->getOptions($data);

    if (empty($options))
      return null;

    $selected = $this->getSelected($data);
    $enabled = $this->getEnabled($data);

    $options = $this->filterOptions($options, $enabled, $selected);

    // Если ни одна опция не разрешена — не выводим контрол.
    if ($enabled !== null and empty($enabled))
      return null;

    $output = '';

    foreach ($options as $k => $v) {
      $disabled = ((null !== $enabled) and !in_array($k, $enabled))
        ? true
        : false;

      $output .= html::em('option', array(
        'value' => $k,
        'selected' => in_array($k, $selected),
        'disabled' => !empty($disabled),
        ), html::cdata($v));
    }

    return parent::wrapXML(array(
      'type' => 'select',
      'mode' => 'set',
      ), $output);
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
    if (!empty($this->dictionary) and $data instanceof Node) {
      $f = $this->parents
        ? 'getLinkedTo'
        : 'getLinked';
      return (array)$data->$f($this->dictionary, true);
    }

    $result = $data->{$this->value};

    if (is_string($result))
      $result = preg_split('/,\s*/', $result, -1, PREG_SPLIT_NO_EMPTY);
    elseif (!is_array($result))
      $result = array();

    return $result;
  }

  protected function getEnabled($data)
  {
    return null;
  }

  protected function filterOptions(array $options, $enabled, $selected)
  {
    return $options;
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    if (empty($this->dictionary) or !($node instanceof Node))
      $node->{$this->value} = $value;
    elseif ($this->parents)
      $this->setParents($node, (array)$value);
    else
      $this->setChildren($node, (array)$value);
  }

  private function setParents(Node &$node, array $value)
  {
    $node->onSave("DELETE FROM `node__rel` WHERE `nid` = %ID% AND `key` IS NULL AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = ?)", array($this->dictionary));
    $params = array();
    $node->onSave("INSERT INTO `node__rel` (`tid`, `nid`) SELECT `id`, %ID% FROM `node` WHERE `id` " . sql::in($value, $params), $params);
  }

  private function setChildren(Node &$node, array $value)
  {
    $node->onSave("DELETE FROM `node__rel` WHERE `tid` = %ID% AND `key` IS NULL AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = ?)", array($this->dictionary));
    $params = array();
    $node->onSave("INSERT INTO `node__rel` (`tid`, `nid`) SELECT %ID%, `id` FROM `node` WHERE `id` " . sql::in($value, $params), $params);
  }

  public function preview($data)
  {
    $nodes = Node::find($filter = array(
      'class' => $this->dictionary,
      'deleted' => 0,
      $this->parents ? 'tagged' : 'tags' => $data->id,
      ));

    $items = array();
    foreach ($nodes as $node)
      $items [$node->id] = $node->getName();
    asort($items);

    $result = array();
    foreach ($items as $k => $v)
      $result[] = html::em('a', array(
        'href' => 'admin/node/' . $k,
        ), html::plain($v));

    $result = empty($result)
      ? null
      : implode(', ', $result) . '.';

    if (empty($result))
      $result = t('нет');

    return html::em('value', array(
      'html' => true,
      ), html::cdata($result));
  }
};
