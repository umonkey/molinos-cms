<?php
/**
 * Контрол для ввода целых чисел.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода целых чисел.
 *
 * @package mod_base
 * @subpackage Controls
 */
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
    return 'INTEGER UNSIGNED';
  }

  public function getHTML($data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (null === ($value = $data->{$this->value}))
      $value = $this->default;

    $output = html::em('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => 'form-text form-number',
      'name' => $this->value,
      'value' => $value,
      ));

    return $this->wrapHTML($output);
  }

  public function set($value, Node &$node, array $data = array())
  {
    $this->validate($value);

    $value = str_replace(',', '.', $value);

    $node->{$this->value} = $value;
  }

  public function getIndexValue($value)
  {
    return floatval($value);
  }
};
