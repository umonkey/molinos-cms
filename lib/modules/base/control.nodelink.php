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
      'name' => t('Выбор из справочника'),
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

  public function getSQL()
  {
    return 'INTEGER';
  }

  public function getXML($data)
  {
    if ($this->hidden)
      return $this->getHidden($data);

    $this->addClass('form-text');
    if (!$this->readonly)
      $this->addClass('autocomplete');

    return parent::wrapXML(array(
      'value' => $this->getCurrentValue($data),
      ));
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
            'text' => $this->default_label
              ? $this->default_label
              : t('(нет)'),
            ));

        foreach ($values as $id => $name) {
          $options .= html::em('option', array(
            'selected' => ($value == $id),
            'value' => $id,
            'text' => $name,
            ));
        }

        return $options;
      }
    }
  }

  // Возвращает текущее значение поля.
  private function getCurrentValue($data)
  {
    if (($tmp = $data->{$this->value}) instanceof NodeStub)
      return $tmp->name;
    return null;
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

  public function getExtraSettings()
  {
    $fields = array(
      'dictionary' => array(
        'type' => 'EnumControl',
        'label' => t('Справочник'),
        'required' => true,
        'options' => TypeNode::getDictionaries(),
        ),
      );

    return $fields;
  }
};
