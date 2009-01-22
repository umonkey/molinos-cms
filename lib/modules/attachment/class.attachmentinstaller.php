<?php

class AttachmentInstaller implements iInstaller
{
  /**
   * Инсталляция.
   *
   * Добавляет тип документа imgtransform.
   */
  public static function onInstall(Context $ctx)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => 'imgtransform',
        ));
    } catch (ObjectNotFoundException $e) {
      $node = Node::create('type', array(
        'name' => 'imgtransform',
        'title' => t('Правила трансформации'),
        ));
      $node->save();
    }
  }

  public static function onUninstall(Context $ctx)
  {
    mcms::debug();
  }
}
