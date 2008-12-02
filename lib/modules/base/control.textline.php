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

  public function getHTML($data)
  {
    if (null === $this->class)
      $this->class = 'form-text';
    else
      $this->class = array_merge(array('form-text'), (array)$this->class);

    $value = $this->getValue($data);

    $output = mcms::html('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => $value,
      'readonly' => $this->readonly ? 'readonly' : null,
      'maxlength' => 255,
      ));

    return $this->wrapHTML($output);
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
