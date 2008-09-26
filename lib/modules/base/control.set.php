<?php
/**
 * Контрол для выбора нескольких значений из списка.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для выбора нескольких значений из списка.
 *
 * Значения представляются в виде набора чекбоксов, объединённых в FieldSet.
 *
 * @package mod_base
 * @subpackage Controls
 */
class SetControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Флаги (несколько галочек)'),
      );
  }

  public function __construct(array $form)
  {
    self::makeOptionsFromValues($form);
    parent::__construct($form, array('value', 'options'));
  }

  public function getHTML(array $data)
  {
    if (!isset($this->options))
      return null;

    $values = array();
    $content = '';

    foreach ($this->options as $k => $v) {
      $inner = mcms::html('input', array(
        'type' => 'checkbox',
        'value' => $k,
        'name' => isset($this->value) ? $this->value .'[]' : null,
        'checked' => !empty($data[$this->value]) and in_array($k, $data[$this->value]),
        ));
      $content .= '<div class=\'form-checkbox\'>'. mcms::html('label', array('class' => 'normal'), $inner . $v) .'</div>';
    }

    return $this->wrapHTML($content);
  }

  protected function makeOptionsFromValues(array &$form)
  {
    if (!empty($form['values']) and !is_array($form['values'])) {
      if (0 === strpos($form['values'], ':')) {
        $nodes = Node::find(array(
          'class' => substr($form['values'], 1),
          'published' => 1,
          '#sort' => 'name',
          ));

        $result = array();

        foreach ($nodes as $node)
          $result[$node->id] = $node->name;

        $form['options'] = $result;
        return;
      }
    }

    parent::makeOptionsFromValues($form);
  }
};
