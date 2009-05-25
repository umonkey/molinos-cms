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

    foreach ((array)$value as $id => $name)
      $result .= html::em('label', array(
        'id' => $id,
        ), html::cdata($name));

    return html::wrap($em, $result);
  }

  public function preview($value)
  {
    $result = array();

    foreach ((array)$value->{$this->value} as $id => $name)
      $result[] = html::em('a', array(
        'href' => 'admin/content/list?search=tags%3A' . $id,
        ), html::cdata($name));

    return html::wrap('value', html::cdata(implode(', ', $result)), array(
      'html' => true,
      ));
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    $node->onSave("DELETE FROM `node__rel` WHERE `nid` = %ID% AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'label')");

    if (empty($value)) {
      unset($node->{$this->value});
    } else {
      $result = array();
      $labels = preg_split('/,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);

      foreach ($labels as $label) {
        try {
          $tmp = Node::load($f = array(
            'class' => 'label',
            'name' => $label,
            'deleted' => 0,
            ), $node->getDB());
        } catch (ObjectNotFoundException $e) {
          $tmp = Node::create('label', array(
            'name' => $label,
            'published' => 1,
            ), $node->getDB())->save();
        }

        $result[$tmp->id] = $tmp->name;
      }

      $params = array();
      $node->onSave($sql = "INSERT INTO `node__rel` (`nid`, `tid`) SELECT %ID%, `id` FROM `node` WHERE `class` = 'label' AND `id` " . sql::in(array_keys($result), $params), $params);

      $node->{$this->value} = $result;
    }
  }

  /**
   * Возвращает строку с текущими метками.
   */
  protected function getValue($data)
  {
    $nodes = Node::find(Context::last()->db, array(
      'class' => 'label',
      'deleted' => 0,
      'tagged' => $data->id,
      ));

    $result = array();
    foreach ($nodes as $node)
      $result[] = $node->name;

    return implode(', ', $result);
  }
}
