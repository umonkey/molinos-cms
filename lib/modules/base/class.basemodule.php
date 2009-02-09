<?php

class BaseModule implements iModuleConfig, iNodeHook
{
  /**
   * Возвращает форму для настройки модуля.
   *
   * @todo вынести в BaseModuleSettings.
   *
   * @return Form форма для настройки модуля.
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new NumberControl(array(
      'value' => 'config_archive_limit',
      'label' => t('Количество архивных ревизий'),
      'default' => 10,
      'description' => t('При сохранении документов будет оставлено указанное количество архивных ревизий, все остальные будут удалены.'),
      )));

    return $form;
  }

  /**
   * Сборщик мусора.
   *
   * При удалении документов удаляет информацию о ревизии, связях и доступе к
   * удаляемому объекту.  Это позволяет отказаться от требования InnoDB и других
   * типов БД, занимающихся каскадным удалением автоматически.
   *
   * @todo вынести в BaseGarbageCollector, может?
   *
   * @return void
   */
  public static function hookNodeUpdate(Node $node, $op)
  {
    switch ($op) {
    case 'erase':
      // Удаляем связи.
      $node->getDB()->exec("DELETE FROM `node__rel` WHERE `nid` = :nid OR `tid` = :tid", array(':nid' => $node->id, ':tid' => $node->id));

      // Удаляем доступ.
      $node->getDB()->exec("DELETE FROM `node__access` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));

      // Удаление статистики.
      $t = new TableInfo($node->getDB(), 'node__astat');
      if ($t->exists())
        $node->getDB()->exec("DELETE FROM `node__astat` WHERE `nid` = :nid", array(':nid' => $node->id));

      break;
    }

    // Обновление структуры сайта.
    if (in_array($node->class, array('domain', 'widget', 'group', 'type'))) {
      $s = new Structure();
      // rebuild() был бы лучше, но он вызывает рекурсию при импорте.
      $s->drop();
    }
  }
};
