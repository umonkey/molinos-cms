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
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
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

  public function getXML($data)
  {
    return parent::wrapXML(array(
      'value' => $this->value ? 1 : $this->value,
      'checked' => empty($data->{$this->value}) ? null : 'yes',
      'disabled' => $this->disabled ? 'yes' : null,
      ));
  }

  public function getSQL()
  {
    return 'tinyint(1)';
  }

  public function set($value, &$node)
  {
    $node->{$this->value} = !empty($value);
  }

  public function getIndexValue($value)
  {
    return $value ? 1 : 0;
  }
};
