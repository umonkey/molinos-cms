<?php

class SectionControl extends EnumControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
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
    $output = array();
    foreach (Node::listChildren('tag') as $item)
      $output[$item[0]] = str_repeat(' ', 2 * $item[2]) . $item[1];
    return $output;
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    if ('pages' == $this->value)
      mcms::debug($this, $value);
    if ($this->store or !($node instanceof Node))
      $node->{$this->value} = $value;
    else {
      $node->onSave("DELETE FROM `node__rel` WHERE `nid` = %ID% AND `key` IS NULL AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'tag')");
      $params = array();
      $node->onSave($sql = "INSERT INTO `node__rel` (`nid`, `tid`) SELECT %ID%, `id` FROM `node` WHERE `class` = 'tag' AND `id` " . sql::in($value, $params), $params);
    }
  }

  protected function getSelected($data)
  {
    if ($this->store or !($data instanceof Node))
      return array($data->{$this->value});
    if (null !== ($result = array_shift($data->getLinkedTo('tag'))))
      $result = $result->id;
    return (array)$result;
  }

  protected function getEnabled($data)
  {
    if ('domain' == $data->class)
      return null;
    if (!($data instanceof Node))
      return null;
    return Node::create($data->class)->getEnabledSections();
  }

  public function getSQL()
  {
    return null;
  }

  public function getExtraSettings()
  {
    return array();
  }

  public function preview($value)
  {
    if ($value) {
      $nodes = Node::find($value->getDB(), array(
        'class' => 'tag',
        'deleted' => 0,
        'tagged' => $value->id,
        ));

      $result = array();
      foreach ($nodes as $node)
        $result[]= html::em('a', array(
          'href' => 'admin/node/' . $node->id,
          ), html::cdata($node->getName()));

      return html::wrap('value', html::cdata(implode(', ', $result)), array(
        'html' => true,
        ));
    }
  }
}