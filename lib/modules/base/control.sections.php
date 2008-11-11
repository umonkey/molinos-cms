<?php

class SectionsControl extends SetControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t("Привязка к разделам"),
      );
  }

  protected function getOptions($data)
  {
    $result = TagNode::getTags('select');

    return $result;
  }

  protected function getSelected($data)
  {
    if (null === ($links = $data->linkListParents('tag', true)))
      return array();

    return $links;
  }

  protected function getEnabled($data)
  {
    return Node::create($data->class)->getEnabledSections();
  }

  public function set($value, Node &$node)
  {
    if (empty($value['__reset']))
      return;
    unset($value['__reset']);

    $this->validate($value);

    $node->linkSetParents($value, 'tag');
  }
}
