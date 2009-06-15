<?php

class TaxonomyInstaller
{
  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => 'tag',
        'deleted' => 0,
        ), $ctx->db);
    } catch (ObjectNotFoundException $e) {
      $node = Node::create(array(
        'class' => 'type',
        'name' => 'tag',
        'title' => t('Раздел'),
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'title' => t('Имя раздела'),
            'required' => true,
            ),
          ),
        ), $ctx->db);
      $node->getDB()->beginTransaction();
      $node->save();
      $node->getDB()->commit();
    }
  }
}
