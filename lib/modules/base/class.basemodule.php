<?php

class BaseModule
{
  /**
   * Сборщик мусора.
   *
   * При удалении документов удаляет информацию о ревизии, связях и доступе к
   * удаляемому объекту.  Это позволяет отказаться от требования InnoDB и других
   * типов БД, занимающихся каскадным удалением автоматически.
   *
   * @mcms_message ru.molinos.cms.hook.node
   *
   * @return void
   */
  public static function hookNodeUpdate(Context $ctx, Node $node, $op)
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
