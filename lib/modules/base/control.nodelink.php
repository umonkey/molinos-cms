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

    $output = mcms::html('input', array(
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
      $output .= mcms::html('input', array(
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
          $options .= mcms::html('option', array(
            'value' => '',
            ), $this->default_label);

        foreach ($values as $id => $name) {
          $options .= mcms::html('option', array(
            'selected' => ($value == $id) ? 'selected' : null,
            'value' => $id,
            ), $name);
        }

        return mcms::html('select', array(
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

    if (empty($value))
      $node->{$this->value} = null;
    else
      $node->{$this->value} = Node::load($value);

    /*
    if (!empty($data['nodelink_remap'][$key]))
      $v['values'] = $data['nodelink_remap'][$key];
    elseif (!empty($v['dictionary']))
      $v['values'] = $v['dictionary'] . '.name';

    $parts = explode('.', $v['values'], 2);

    if (empty($value)) {
      $node = null;
    } elseif (is_numeric($value) and empty($data['nodelink_remap'][$key])) {
      // Обработка обычных выпадающих списков.
      try {
        $node = Node::load($f = array(
          'class' => $parts[0],
          'id' => $value,
          'deleted' => 0,
          ));
      } catch (ObjectNotFoundException $e) {
        $node = null;
      }
    } elseif (!empty($v['values'])) {
      $required = substr($parts[1], -1) == '!';

      $filter = array(
        'class' => $parts[0],
        rtrim($parts[1], '!') => $value,
        'published' => 1,
        'deleted' => 0,
        );

      $node = Node::find($filter, 1);

      if (!empty($node))
        $node = array_shift($node);
      else
        $node = null;

      if (empty($node) and $required)
        throw new ValidationException(t('Не заполнено поле «%field».',
          array('%field' => $v['label'])));
    }

    $this->$k = $node;
    */
  }

  public function getLinkId($data)
  {
    if (null !== ($value = $data->{$this->value}))
      return Node::_id($value);
  }
};
