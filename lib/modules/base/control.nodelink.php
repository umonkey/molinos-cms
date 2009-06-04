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

  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
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

    if (empty($form['description']))
      $form['description'] = t('Введите часть названия для поиска по базе данных.');

    parent::__construct($form, array('value'));
  }

  public function getSQL()
  {
    return null;
  }

  public function getXML($data)
  {
    if ($this->hidden)
      return $this->getHidden($data);

    if ($tmp = $this->getSelect($data))
      return parent::wrapXML(array(
        'value' => $this->getCurrentValue($data),
        ), $tmp);

    $this->addClass('form-text');
    if (!$this->readonly)
      $this->addClass('autocomplete');

    return parent::wrapXML(array(
      'type' => 'text',
      'mode' => 'autocomplete',
      ), html::cdata($this->getCurrentValue($data)));
  }

  // Возвращает текущее значение поля.
  private function getCurrentValue($data)
  {
    if (($tmp = $data->{$this->value}) instanceof Node)
      return $tmp->name;
    return null;
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    try {
      if (empty($value))
        $node->{$this->value} = null;
      elseif (is_numeric($value))
        $node->{$this->value} = Node::load($value);
      else {
        $parts = explode('.', $this->values);

        if ($node instanceof Node)
          $db = $node->getDB();
        else
          $db = Context::last()->db;

        $n = Node::find(array(
          'class' => $parts[0],
          'deleted' => 0,
          $parts[1] => $value,
          ));

        if (!empty($n))
          $n = array_shift($n);
        else
          $n = Node::create(array(
            'class' => $parts[0],
            $parts[1] => $value,
            'published' => 1,
            ));

        $node->{$this->value} = $n;
      }
    } catch (TableNotFoundException $e) {
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
        'weight' => 4,
        ),
      'nonew' => array(
        'type' => 'BoolControl',
        'label' => t('Запретить прозрачное добавление'),
        'weight' => 5,
        ),
      );

    return $fields;
  }

  /**
   * Возвращает выпадающий список при небольшом количестве значений.
   */
  protected function getSelect($data)
  {
    if (!$this->nonew)
      return null;

    $db = Context::last()->db;
    $parts = explode('.', $this->values);

    $q = new Query($filter = array(
      'class' => $parts[0],
      'published' => 1,
      'deleted' => 0,
      '#sort' => 'name',
      ));

    list($sql, $params) = $q->getCount();

    if (($count = $db->fetch($sql, $params) < 50)) {
      $result = '';
      $current = $this->getCurrentValue($data);
      foreach (Node::find($filter) as $node)
        $result .= html::em('option', array(
          'value' => $node->name,
          'selected' => ($current == $node->name) ? 'yes' : null,
          ));
      return html::em('options', $result);
    }
  }
};
