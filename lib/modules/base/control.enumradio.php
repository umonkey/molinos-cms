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

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML($data)
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

    $options = '';

    if (is_array($this->options))
      foreach ($this->options as $k => $v) {
        $option = html::em('input', array(
          'type' => 'radio',
          'class' => 'form-radio',
          'name' => $this->value,
          'checked' => ($selected == $k) ? 'checked' : null,
          'value' => $k,
          ));
        $options .= html::em('label', array('class' => 'radio'), $option .'<span>'. $v .'</span>');
      }

    if (empty($options))
      return '';

    if (isset($this->label))
      $caption = html::em('legend', array('class' => 'radio'),
        html::em('span', $this->label));
    else
      $caption = null;

    return $this->wrapHTML(html::em('fieldset', $caption . $options), false);
  }
};
