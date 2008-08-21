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
    return 'DECIMAL(10,2)';
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    $output = mcms::html('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => 'form-text form-number',
      'name' => $this->value,
      'value' => empty($data[$this->value]) ? $this->default : $data[$this->value],
      ));

    return $this->wrapHTML($output);
  }
};
