<?php

class LabelsInstaller
{
  /**
   * Добавляет тип документа label.
   *
   * @mcms_message ru.molinos.cms.install
   */
  public static function onInstall(Context $ctx)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => 'label',
        ), $ctx->db);
    } catch (ObjectNotFoundException $e) {
      $ctx->db->beginTransaction();
      $node = Node::create(array(
        'class' => 'type',
        'name' => 'label',
        'label' => t('Метка'),
        'isdictionary' => true,
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'title' => t('Метка'),
            'required' => true,
            ),
          ),
        ), $ctx->db)->save();
      $ctx->db->commit();
    }
  }
}
