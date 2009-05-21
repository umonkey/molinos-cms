<?php
/**
 * Контрол для выбора значения из нескольких вариантов (радио).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для выбора значения из нескольких вариантов (радио).
 *
 * @package mod_base
 * @subpackage Controls
 */
class EnumRadioControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
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

  public function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getXML($data)
  {
    $selected = $data->{$this->value};

    if (null === $selected) {
      if (null !== $this->default and array_key_exists($this->default, $this->options))
        $selected = $this->default;
      elseif ($this->required) {
        $tmp = array_keys($this->options);
        $selected = $tmp[0];
      }
    }

    if (count($options = $this->getData())) {
      $output = '';

      foreach ($options as $k => $v) {
        $output .= html::em('option', array(
          'selected' => ($selected == $k),
          'value' => $k,
          ), html::cdata($v));
      }
    }

    if (!empty($output))
      return parent::wrapXML(array(
        'type' => 'select',
        'mode' => 'radio',
        ), $output);
  }

  private function getData()
  {
    $list = array();

    if (!$this->required)
      $list[''] = $this->default_label;

    if (isset($this->dictionary))
      $list = array_merge($list, Node::getSortedList($this->dictionary));

    elseif (is_array($this->options))
      $list = array_merge($list, $this->options);

    return $list;
  }
};
