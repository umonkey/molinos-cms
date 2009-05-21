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
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
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

  public function getSQL()
  {
    return 'DECIMAL(10,2)';
  }

  public function getXML($data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (null === ($value = $data->{$this->value}))
      $value = $this->default;

    if ($value == $this->default)
      $value = null;

    return parent::wrapXML(array(
      'type' => 'text',
      'mode' => 'number',
      'value' => $value,
      ));
  }

  public function set($value, &$node, array $data = array())
  {
    if (empty($value))
      $value = $this->default;

    $this->validate($value);

    $value = str_replace(',', '.', $value);

    $node->{$this->value} = $value;
  }

  public function getIndexValue($value)
  {
    return floatval($value);
  }

  protected function validate($value)
  {
    if (!empty($value) and !is_numeric($value))
      throw new ValidationException($this->label ? $this->label : $this->value);
  }
};
