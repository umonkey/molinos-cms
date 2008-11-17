<?php
/**
 * Контрол для выбора значения из выпадающего списка.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для выбора значения из выпадающего списка.
 *
 * @package mod_base
 * @subpackage Controls
 */
class EnumControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Выбор из списка (выпадающего)'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['default_label']))
      $form['default_label'] = t('(не выбрано)');

    parent::makeOptionsFromValues($form);
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'VARCHAR(255)';
  }

  public function getHTML($data)
  {
    $options = $this->getData($data);

    $selected = $this->getSelected($data);
    $enabled = $this->getEnabled($data);

    if (is_array($enabled) and count($enabled) == 1) {
      $id = array_shift(array_values($enabled));

      if (array_key_exists($id, $options)) {
        return mcms::html('input', array(
          'type' => 'hidden',
          'name' => $this->value,
          'value' => $id,
          ));
      }
    }

    $output = '';

    if (!$this->required)
      $output .= mcms::html('option', array(
        'value' => '',
        ), $this->default_label);

    foreach ($this->getData($data) as $k => $v) {
      $output .= mcms::html('option', array(
        'value' => $k,
        'selected' => in_array($k, $selected) ? 'selected' : null,
        'disabled' => (null === $enabled or in_array($k, $enabled)) ? null : 'disabled',
        ), $v);
    }

    if (empty($output))
      return '';

    $output = mcms::html('select', array(
      'id' => $this->id,
      'name' => $this->value,
      ), $output);

    return $this->wrapHTML($output);
  }

  protected function getData($data)
  {
    if (!is_array($result = $this->options))
      $result = array();

    return $result;
  }

  protected function getEnabled($data)
  {
    return null;
  }

  protected function getSelected($data)
  {
    if ($value = $data->{$this->value})
      return array($value);

    return array();
  }
};
