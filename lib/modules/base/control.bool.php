<?php
/**
 * Контрол "флаг" (чекбокс).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол "флаг" (чекбокс).
 *
 * @package mod_base
 * @subpackage Controls
 */
class BoolControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Флаг'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML($data)
  {
    $checked = empty($data->{$this->value})
      ? null
      : 'checked';

    $output = html::em('input', array(
      'type' => 'checkbox',
      'name' => $this->value,
      'value' => $this->value ? 1 : $this->value,
      'checked' => $checked,
      'disabled' => $this->disabled ? 'disabled' : null,
      ));

    if (isset($this->label))
      $output = html::em('label', array(
        'id' => $this->id,
        ), $output . html::em('span', $this->label));

    return $this->wrapHTML($output, false);
  }

  public static function getSQL()
  {
    return 'tinyint(1)';
  }

  public function set($value, Node &$node)
  {
    $node->{$this->value} = !empty($value);
  }

  public function getIndexValue($value)
  {
    return $value ? 1 : 0;
  }
};
