<?php

class SectionControl extends EnumControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t("Привязка к разделу"),
      );
  }

  public function __construct(array $form)
  {
    $form['indexed'] = false;

    if (!array_key_exists('default_label', $form))
      $form['default_label'] = t('(не выбран)');

    parent::__construct($form, array('value'));
  }

  protected function getData($data)
  {
    return TagNode::getTags('select');
  }

  public function set($value, Node &$node)
  {
    $this->validate($value);

    if ($this->store)
      $node->{$this->value} = $value;
    else
      $node->linkSetParents(array($value), 'tag', $this->getEnabled($node));
  }

  protected function getSelected($data)
  {
    if ($this->store or !($data instanceof Node) or $this->dictionary == 'widget')
      return array($data->{$this->value});

    $links = $data->linkListParents('tag', true);

    if (null === $links)
      return array();

    return $links;
  }

  protected function getEnabled($data)
  {
    if ('domain' == $data->class)
      return null;
    if (!($data instanceof Node))
      return null;
    return Node::create($data->class)->getEnabledSections();
  }
}
