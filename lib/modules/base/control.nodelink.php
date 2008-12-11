<?php
/**
 * Контрол для построения связей между документами.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для построения связей между документами.
 *
 * Позволяет связать один документ с другим.  Связь сохраняется в таблице связей
 * (node__rel), в качестве ключа (поле key) используется имя поля в документе.
 *
 * Для больших списков используется текстовое поле с возможностью ввести имя
 * желаемого объекта, которое, естественно, должно быть уникальным (в случае
 * чего используется первый попавшийся объект с указанным именем).  Для удобства
 * используется плагин jQuery Suggest (имитирует фильтрованый выпадающий список,
 * обновляемый по мере ввода информации).
 *
 * Для выбора из менее чем 50 значений используется обычный выпадающий список
 * (select).
 *
 * @package mod_base
 * @subpackage Controls
 */
class NodeLinkControl extends Control
{
  const limit = 50;

  public static function getInfo()
  {
    return array(
      'name' => t('Связь с документом'),
      );
  }

  public function __construct(array $form)
  {
    if (!empty($form['dictionary'])) {
      if ('user' == $form['dictionary'])
        $form['values'] = 'user.fullname';
      else
        $form['values'] = $form['dictionary'] . '.name';

      unset($form['dictionary']);
    }

    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'int';
  }

  public function getHTML($data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (null !== ($output = $this->getSelect(strval($this->getCurrentValue($data)))))
      return $this->wrapHTML($output);

    $parts = explode('.', $this->values, 2);

    if (($value = $this->getCurrentValue($data)) instanceof Node)
      $name = $value->$parts[1];
    elseif (is_numeric($value))
      $name = Node::load($value)->$parts[1];
    else
      $name = '';

    $this->addClass('form-text');

    if (!$this->readonly) {
      $this->addClass('autocomplete');

      mcms::extras('themes/all/jquery/plugins/jquery.suggest.js');
      mcms::extras('themes/all/jquery/plugins/jquery.suggest.css');
    }

    $output = html::em('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => $this->class,
      'autocomplete' => 'off',
      'name' => $this->value,
      'value' => $name,
      'readonly' => $this->readonly ? 'readonly' : null,
      ));

    if (!$this->readonly) {
      $output .= '<script language=\'javascript\' type=\'text/javascript\'>$(function(){$(\'#'. $this->id .'\').suggest(\'?q=autocomplete.rpc&source='. $this->values .'\');});</script>';
      $output .= html::em('input', array(
        'type' => 'hidden',
        'name' => "nodelink_remap[{$this->value}]",
        'value' => $this->values . ($this->required ? '!' : ''),
        ));
    }

    return $this->wrapHTML($output);
  }

  private function getSelect($value)
  {
    if (count($parts = explode('.', $this->values, 2)) == 2) {
      if (Node::count($filter = array('class' => $parts[0], 'published' => 1, '#sort' => array('name' => 'asc'))) < self::limit) {
        foreach ($z = Node::find($filter) as $tmp)
          $values[$tmp->id] = $tmp->getName();

        asort($values);

        $options = '';

        if (!$this->required)
          $options .= html::em('option', array(
            'value' => '',
            ), $this->default_label);

        foreach ($values as $id => $name) {
          $options .= html::em('option', array(
            'selected' => ($value == $id) ? 'selected' : null,
            'value' => $id,
            ), $name);
        }

        return html::em('select', array(
          'name' => $this->value,
          ), $options);
      }
    }
  }

  // Возвращает текущее значение поля.
  private function getCurrentValue($data)
  {
    return $data->{$this->value};
  }

  public function set($value, Node &$node)
  {
    $this->validate($value);

    try {
      if (empty($value))
        $node->{$this->value} = null;
      elseif (is_numeric($value))
        $node->{$this->value} = Node::load($value);
      else {
        $parts = explode('.', $this->values);

        $n = Node::load(array(
          'class' => $parts[0],
          $parts[1] => $value,
          ));

        $node->{$this->value} = $n;
      }
    } catch (ObjectNotFoundException $e) {
      throw new PageNotFoundException(t('Объект «%name» не найден.', array(
        '%name' => $value,
        )));
    }
  }

  public function getLinkId($data)
  {
    if (null !== ($value = $data->{$this->value}))
      return Node::_id($value);
  }
};
