<?php
/**
 * Контрол для ввода текстовой строки.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода текстовой строки.
 *
 * @package mod_base
 * @subpackage Controls
 */
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

  public function getXML($data)
  {
    $this->addClass('form-text');

    return parent::wrapXML(array(
      'value' => $this->getValue($data),
      'maxlength' => 255,
      ));
  }

  protected function getValue($data)
  {
    if (null === ($value = $data->{$this->value}))
      $value = $this->default;

    return $value;
  }

  public function set($value, Node &$node)
  {
    $this->validate($value);

    $node->{$this->value} = $value;
  }
};
