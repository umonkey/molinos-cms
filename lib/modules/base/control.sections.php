<?php

class SectionsControl extends SetControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t("Привязка к разделам"),
      );
  }

  protected function getOptions($data)
  {
    if (($data instanceof TypeNode) and $data->name == 'file')
      return null;

    $output = array();
    foreach (Node::listChildren('tag') as $item)
      $output[$item[0]] = str_repeat('  ', $item[2]) . $item[1];
    return $output;
  }

  protected function getSelected($data)
  {
    if ($this->store)
      return (array)$data->{$this->value};
    elseif (!($data instanceof Node))
      return array();
    else {
      $links = array();
      foreach ($data->getLinkedTo('tag') as $node)
        $links[] = $node->id;
      return $links;
    }
  }

  protected function getEnabled($data)
  {
    if ($data instanceof Node)
      return Node::create($data->class)->getEnabledSections();
    else
      return null;
  }

  public function set($value, &$node)
  {
    if (empty($value['__reset']))
      return;
    unset($value['__reset']);

    $this->validate($value);

    if ($this->store or !($node instanceof Node))
      $node->{$this->value} = $value;
    else {
      $node->onSave("DELETE FROM `node__rel` WHERE `nid` = %ID% AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'tag')");
      if (!empty($value)) {
        $params = array();
        $node->onSave($sql = "INSERT INTO `node__rel` (`tid`, `nid`) SELECT `id`, %ID% FROM `node` WHERE `class` = 'tag' AND `id` " . sql::in($value, $params), $params);
      }
    }
  }
}
