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

    if (!$this->store)
      $node->linkAddParent($value, $this->value);

    $node->{$this->value} = $value;
  }

  protected function getSelected($data)
  {
    if ($this->store)
      return array($data->{$this->value});

    if (null === ($links = $data->linkListParents('tag', true)))
      return array();

    return in_array($data->{$this->value}, $links)
      ? array(intval($data->{$this->value}))
      : array();
  }

  protected function getEnabled($data)
  {
    return Node::create($data->class)->getEnabledSections();
  }
}
