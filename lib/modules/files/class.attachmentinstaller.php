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
        ));
    } catch (ObjectNotFoundException $e) {
      $ctx->db->beginTransaction();
      $node = Node::create('type', array(
        'name' => 'imgtransform',
        'title' => t('Правила трансформации'),
        ))->save();
      $ctx->db->commit();
    }
  }
}
