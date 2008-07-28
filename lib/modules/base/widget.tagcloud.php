<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TagCloudWidget extends Widget
{
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Облако тэгов',
      'description' => 'Выводит список тэгов, содержащих доступные пользователю документы.',
      );
  }

  public static function formGetConfig()
  {
    $types = array();

    foreach (Node::find(array('class' => 'type')) as $type)
      if (!in_array($type->name, TypeNode::getInternal()))
        $types[$type->name] = $type->title;

    $form = parent::formGetConfig();

    $form->addControl(new SetControl(array(
      'value' => 'config_classes',
      'label' => t('Типы документов'),
      'options' => $types,
      )));

    return $form;
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = array(
      // 'types' => mcms::user()->getAccess('r'),
      'types' => $this->classes,
      );

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    if (empty($options['types']))
      return null;
    $types = "'". join("', '", $options['types']) ."'";

    $t1 = microtime(true);

    /*
    $data = mcms::db()->getResults($sql = 'SELECT n.id AS id, v.name AS name, '
      .'COUNT(*) AS cnt '
      .'FROM node n INNER JOIN node__rev v ON v.rid = n.rid '
      .'INNER JOIN node__rel r ON r.tid = n.id '
      .'WHERE n.class = \'tag\' '
      .'AND n.published = 1 '
      .'AND n.deleted = 0 '
      .'GROUP BY n.id, v.name '
      .'ORDER BY v.name');
    */
    $data = mcms::db()->getResults($sql = 'SELECT n.id AS id, v.name AS name, '
      .'COUNT(*) AS cnt '
      .'FROM node n INNER JOIN node__rev v ON v.rid = n.rid '
      .'INNER JOIN node__rel r ON r.tid = n.id '
      .'WHERE n.class = \'tag\' '
      .'AND n.published = 1 '
      .'AND n.deleted = 0 '
      .'AND r.nid IN (SELECT id FROM node WHERE published = 1 AND deleted = 0 AND class IN ('. $types .')) '
      .'GROUP BY n.id, v.name '
      .'ORDER BY v.name');

    $t2 = microtime(true);

    // Calculate the total number of docs.
    $total = 0;
    foreach ($data as $k => $v)
      $total += $v['cnt'];

    // Set percentage.
    foreach ($data as $k => $v)
      $data[$k]['percent'] = intval(100 / $total * $v['cnt']);

    $result = array('tags' => $data);

    return $result;
  }
}
