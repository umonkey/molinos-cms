<?php

class CloneAPI
{
  /**
   * Добавляет специфичные действия для типов документов.
   *
   * @mcms_message ru.molinos.cms.node.actions
   */
  public static function on_get_actions(Context $ctx, Node $node)
  {
    $result = array();

    if ($node->id and $node->checkPermission('c')) {
      $result['clone'] = array(
        'href' => "clone.rpc?id={$node->id}&destination=CURRENT",
        'title' => t('Клонировать'),
        );
    }

    return $result;
  }

  /**
   * Добавляет clone.rpc в маршруты.
   * @mcms_message ru.molinos.cms.route.poll
   */
  public static function on_route_poll()
  {
    return array(
      'GET//clone.rpc' => array(
        'call' => __CLASS__ . '::on_clone',
        ),
      );
  }

  /**
   * Клонирование объекта.
   */
  public static function on_clone(Context $ctx)
  {
    $ctx->db->beginTransaction();
    $node = Node::load($ctx->get('id'))->knock('c');
    $node->uid = $ctx->user->getNode();
    $node->duplicate()
      ->onSave("DELETE FROM `node__rel` WHERE `tid` = %ID% AND `key` = 'uid'")
      ->onSave("INSERT INTO `node__rel` (`tid`, `nid`, `key`) VALUES (%ID%, ?, ?)", array($ctx->user->id, 'uid'))
      ->save()
      ->updateXML();
    $ctx->db->commit();

    $ctx->redirect("admin/node/{$node->id}?destination=" . urlencode($ctx->get('destination')));
  }
}
