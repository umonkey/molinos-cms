<?php
/**
 * Контрол для выбора значения из выпадающего списка.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для выбора значения из выпадающего списка.
 *
 * @package mod_base
 * @subpackage Controls
 */
class EnumControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Выбор из списка (выпадающего)'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['default_label']))
      $form['default_label'] = t('(не выбрано)');

    if (empty($form['prepend']))
      $form['prepend'] = array();

    parent::makeOptionsFromValues($form);
    parent::__construct($form, array('value'));
  }

  public function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getXML($data)
  {
    $options = '';

    if (!$this->required)
      $options .= html::em('option', array(
        'value' => '',
        ), html::cdata($this->default_label));

    $enabled = $this->getEnabled($data);

    if (is_array($enabled) and count($enabled) == 1)
      return html::em('input', array(
        'type' => 'hidden',
        'name' => $this->value,
        'value' => array_shift($enabled),
        ));

    $selected = $this->getSelected($data);

    $list = $this->prepend + $this->getData($data);

    foreach ($list as $k => $v) {
      $options .= html::em('option', array(
        'value' => $k,
        'selected' => in_array($k, $selected),
        'disabled' => !(null === $enabled or in_array($k, $enabled)),
        ), html::cdata($v));
    }

    return parent::wrapXML(array(
      'type' => 'select',
      'mode' => $this->getMode(),
      ), $options);
  }

  protected function getData($data)
  {
    if (isset($this->dictionary))
      return Node::getSortedList($this->dictionary);

    if (!is_array($result = $this->options))
      $result = array();

    return $result;
  }

  protected function getEnabled($data)
  {
    return null;
  }

  protected function getSelected($data)
  {
    if (null === ($value = $data->{$this->value}))
      $value = $this->default;

    if (empty($value))
      return array();

    if (is_object($value))
      $value = $value->id;

    return array($value);
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    if (empty($value))
      unset($node->{$this->value});
    elseif ($this->dictionary)
      $node->{$this->value} = Node::load($value);
    else
      $node->{$this->value} = $value;
  }

  public function getExtraSettings()
  {
    $fields = array(
      'dictionary' => array(
        'type' => 'EnumControl',
        'label' => t('Справочник'),
        'options' => TypeNode::getDictionaries(),
        'weight' => 4,
        'default_label' => t('(не использовать)'),
        ),
      );

    return $fields;
  }

  protected function getMode()
  {
    return 'drop';
  }

  public function format($value, $em)
  {
    if (!empty($value) and is_object($value))
      return $value->getXML($em);
  }

  public function preview($value)
  {
    if ($value = $value->{$this->value}) {
      if (is_object($value)) {
        $link = html::em('a', array(
          'href' => 'admin/node/' . $value->id,
          ), html::plain($value->name));
        return html::wrap('value', html::cdata($link), array(
          'html' => true,
          ));
      }
    }
  }
};
