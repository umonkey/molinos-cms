<?php

class LabelsControl extends ListControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Метки'),
      );
  }

  public function format($value, $em)
  {
    $result = '';

    if (!empty($value) and is_array($value)) {
      $params = array();
      $data = (array)Context::last()->db->getResults($sql = "SELECT `id`, `published`, `name` FROM `node` WHERE `class` = 'label' AND `deleted` = 0 AND `id` " . sql::in(array_keys($value), $params), $params);

      foreach ($data as $row)
        $result .= html::em('label', array(
          'id' => $row['id'],
          'published' => (bool)$row['published'],
          ), html::cdata($row['name']));
    }

    return html::wrap($em, $result);
  }

  /**
   * Формирование предварительного просмотра.
   *
   * Загружает данные прямо из БД, чтобы видеть метки, которые не дошли
   * до XML представления.  Такие метки выделяются курсивом.
   */
  public function preview($value)
  {
    if ($labels = $value->{$this->value}) {
      $result = array();

      foreach ($this->getLabelsFor($value) as $id => $name) {
        if (!array_key_exists($id, $labels))
          $name = html::em('em', $name);
        $result[] = html::em('a', array(
          'href' => 'admin/node/' . $id,
          // 'href' => 'admin/content/list?search=tags%3A' . $id,
          ), $name);
      }

      return html::wrap('value', html::cdata(implode(', ', $result)), array(
        'html' => true,
        ));
    }
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    $fieldName = $this->value . '*';
    $node->onSave("DELETE FROM `node__rel` WHERE `nid` = %ID% AND `key` = ?", array($fieldName));

    if (empty($value)) {
      unset($node->{$this->value});
    } else {
      $result = array();
      $labels = preg_split('/,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);

      foreach ($labels as $label) {
        try {
          $label = trim($label);
          $tmp = Node::load($f = array(
            'class' => 'label',
            'name' => $label,
            'deleted' => 0,
            ), $node->getDB());
        } catch (ObjectNotFoundException $e) {
          $tmp = Node::create(array(
            'class' => 'label',
            'name' => $label,
            'published' => 1,
            ), $node->getDB())->save();
        }

        $result[$tmp->id] = $tmp->name;
      }

      $params = array($fieldName);
      $node->onSave($sql = "INSERT INTO `node__rel` (`nid`, `tid`, `key`) SELECT %ID%, `id`, ? FROM `node` WHERE `class` = 'label' AND `id` " . sql::in(array_keys($result), $params), $params);

      $node->{$this->value} = $result;
    }
  }
 
  /**
   * Возвращает строку с текущими метками.
   */
  protected function getValue($data)
  {
    if ($data instanceof Node)
      return implode(', ', $this->getLabelsFor($data));
  }

  /**
   * Возвращает список меток для документа.
   */
  protected function getLabelsFor(Node $node)
  {
    if (!$node->id)
      return array();
    return array_unique((array)$node->getDB()->getResultsKV('id', 'name', "SELECT `id`, `name` FROM `node` "
      . "WHERE `class` = 'label' AND `deleted` = 0 AND `id` "
      . "IN (SELECT `tid` FROM `node__rel` WHERE `nid` = ? AND `key` = ?)",
      array($node->id, $this->value . '*')));
  }

  /**
   * Запрет на индексирование меток.
   */
  public function getSQL()
  {
    return false;
  }
}
