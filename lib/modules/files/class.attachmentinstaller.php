<?php

class AttachmentInstaller
{
  /**
   * Добавляет тип документа imgtransform.
   *
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => 'imgtransform',
        ), $ctx->db);
    } catch (ObjectNotFoundException $e) {
      $node = Node::create('type', array(
        'name' => 'imgtransform',
        'title' => t('Правила трансформации'),
        ));
      $node->getDB()->beginTransaction();
      $node->save();
      $node->getDB()->commit();
    }
  }
}
