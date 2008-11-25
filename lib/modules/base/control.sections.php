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
    if (($data instanceof TypeNode) and $data->name == 'file')
      return null;

    $result = TagNode::getTags('select');

    return $result;
  }

  protected function getSelected($data)
  {
    if ($this->store)
      return (array)$data->{$this->value};
    elseif (null === ($links = $data->linkListParents('tag', true)))
      return array();

    return $links;
  }

  protected function getEnabled($data)
  {
    if ($data instanceof Node)
      return Node::create($data->class)->getEnabledSections();
    else
      return null;
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
