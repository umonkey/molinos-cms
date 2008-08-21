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

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (array_key_exists($this->value, $data))
      $checked = empty($data[$this->value]) ? null : 'checked';
    else
      $checked = empty($this->default) ? null : 'checked';

    $output = mcms::html('input', array(
      'type' => 'checkbox',
      'name' => $this->value,
      'value' => $this->value ? 1 : $this->value,
      'checked' => $checked,
      'disabled' => $this->disabled ? 'disabled' : null,
      ));

    if (isset($this->label))
      $output = mcms::html('label', array(
        'id' => $this->id,
        ), $output . mcms::html('span', $this->label));

    return $this->wrapHTML($output, false);
  }

  public static function getSQL()
  {
    return 'tinyint(1)';
  }
};
