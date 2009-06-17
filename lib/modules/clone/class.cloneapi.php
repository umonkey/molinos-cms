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
   * Клонирование объекта.
   * @GET//clone.rpc
   */
  public static function on_clone(Context $ctx)
  {
    $ctx->db->beginTransaction();
    $node = Node::load($ctx->get('id'))->knock('c');

    $node->published = false;
    $node->deleted = false;
    $node->created = null;
    $node->parent_id = $ctx->get('parent');

    // Копируем связи с другими объектами.
    $node->onSave("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) "
      ."SELECT %ID%, `nid`, `key` FROM `node__rel` WHERE `tid` = ?", array($node->id));
    $node->onSave("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) "
      ."SELECT `tid`, %ID%, `key` FROM `node__rel` WHERE `nid` = ?", array($node->id));

    $ctx->registry->broadcast('ru.molinos.cms.node.clone', array($node));

    $node->id = null;
    $node->save();
    $node->updateXML();

    $ctx->db->commit();

    $ctx->redirect("admin/node/{$node->id}?destination=" . urlencode($ctx->get('destination')));
  }
}
