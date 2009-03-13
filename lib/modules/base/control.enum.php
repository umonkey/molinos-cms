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
      'class' => __CLASS__,
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
        'text' => $this->default_label,
        ));

    $enabled = $this->getEnabled($data);

    if (is_array($enabled) and count($enabled) == 1)
      return html::em('control', array(
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
        'text' => $v,
        ));
    }

    return empty($options)
      ? null
      : parent::wrapXML(array(), $options);
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
    if ($value = $data->{$this->value})
      return array($value);

    return array();
  }
};
