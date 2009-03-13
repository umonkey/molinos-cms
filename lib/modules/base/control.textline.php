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
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Текстовая строка'),
      'class' => __CLASS__,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getSQL()
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

  public function getExtraSettings()
  {
    return array(
      're' => array(
        'type' => 'TextLineControl',
        'label' => t('Проверка значений'),
        'description' => t('Здесь можно ввести регулярное выражение, например, @^[a-z0-9]+$@ позволит ввести только цифры и буквы.'),
        ),
      );
  }
};
