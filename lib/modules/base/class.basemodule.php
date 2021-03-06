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

      break;
    }
  }
};
