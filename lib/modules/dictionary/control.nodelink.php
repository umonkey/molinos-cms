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

    $map = array(
      'select' => 'drop',
      );
    if (isset($map[$mode = $this->mode]))
      $mode = $map[$mode];

    if ('enter' != $mode)
      return parent::wrapXML(array(
        'type' => 'select',
        'mode' => $mode,
        'value' => $this->getCurrentValue($data),
        ), $this->getSelect($data));

    return parent::wrapXML(array(
      'type' => 'text',
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
    if ($value) {
      $node->link((array)$value, true, $this->value);
      $node->{$this->value} = $value;
    }
  }

  public function getLinkId($data)
  {
    if (null !== ($value = $data->{$this->value}))
      return Node::_id($value);
  }

  /**
   * Дополнительные настройки поля.
   */
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
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим работы'),
        'options' => array(
          'select' => t('один — выпадающий список'),
          'radio' => t('один — радио'),
          'enter' => t('один — текстовое поле'),
          'set' => t('много — галки'),
          ),
        'required' => true,
        ),
      'details' => array(
        'type' => 'EnumControl',
        'label' => t('Объём данных'),
        'options' => array(
          'less' => t('id + название'),
          'more' => t('полный XML'),
          ),
        'required' => true,
        ),
      );

    return $fields;
  }

  /**
   * Возвращает выпадающий список при небольшом количестве значений.
   */
  protected function getSelect($data)
  {
    $db = Context::last()->db;

    $selected = $data->id
      ? $db->getResultsV("nid", "SELECT `nid` FROM {node__rel} WHERE `tid` = ? AND `key` = ?", array($data->id, $this->value))
      : array();

    $data = $db->getResultsKV("id", "name", "SELECT `id`, `name` FROM {node} WHERE `class` = ? AND `deleted` = 0 ORDER BY `name`", array($this->dictionary));

    $options = '';
    foreach ($data as $k => $v)
      $options .= html::em('option', array(
        'value' => $k,
        'selected' => in_array($k, $selected),
        ), html::plain($v));

    return $options;
  }

  /**
   * Возвращает имя связанного объекта для предварительного просмотра.
   */
  public function preview($value)
  {
    if (is_object($value = $value->{$this->value})) {
      $html = html::em('a', array(
        'href' => 'admin/node/' . $value->id
          . '?destination=CURRENT',
        ), html::plain($value->getName()));
      return html::em('value', array(
        'html' => true,
        ), html::cdata($html));
    }
  }

  /**
   * Вставляет ноду в родительский объект.
   */
  public function format(Node $node, $em)
  {
    $ids = array();
    foreach ((array)$node->{$this->value} as $v)
      if (empty($v))
        ;
      elseif (is_object($v))
        $ids[] = $v->id;
      elseif (is_array($v)) // как так получается?!
        $ids[] = $v['id'];
      else
        $ids[] = $v;

    if ('more' == $this->details)
      $result = Node::findXML(array(
        'class' => $this->dictionary,
        'deleted' => 0,
        'id' => $ids,
        ));
    else {
      $params = array($this->dictionary);
      $data = Context::last()->db->getResults("SELECT `id`, `class`, `published`, `name` FROM {node} WHERE `class` = ? AND `id` " . sql::in($ids, $params), $params);
      $result = '';
      foreach ($data as $row)
        $result .= html::em('node', $row);
    }

    if (empty($result))
      mcms::debug($ids, $node);

    return html::em($em, $result);
  }
};
