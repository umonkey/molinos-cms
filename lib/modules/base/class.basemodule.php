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
    $form->addControl(new SetControl(array(
      'value' => 'config_new_user_groups',
      'label' => t('Группы для регистрирующихся пользователей'),
      'options' => Node::getSortedList('group'),
      'store' => true,
      )));
    $form->addControl(new SetControl(array(
      'value' => 'config_special_profile_fields',
      'label' => t('Запретить пользователям редактировать поля'),
      'options' => self::getProfileFields(),
      'store' => true,
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_check_pw_on_profile_edit',
      'label' => t('Проверять пароль при изменении профиля'),
      )));

    return $form;
  }

  private static function getProfileFields()
  {
    $result = array();

    foreach (Schema::load('user') as $k => $v)
      $result[$k] = $v->label;

    if (isset($result['groups']))
      unset($result['groups']);

    asort($result);
    return $result;
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
      // Удаляем расширенные данные.
      $t = new TableInfo('node_'. $node->class);
      if ($t->exists())
        mcms::db()->exec("DELETE FROM `node_{$node->class}` WHERE `rid` IN (SELECT `rid` FROM `node__rev` WHERE `nid` = :nid)", array(':nid' => $node->id));

      // Удаляем все ревизии.
      mcms::db()->exec("DELETE FROM `node__rev` WHERE `nid` = :nid", array(':nid' => $node->id));

      // Удаляем связи.
      mcms::db()->exec("DELETE FROM `node__rel` WHERE `nid` = :nid OR `tid` = :tid", array(':nid' => $node->id, ':tid' => $node->id));

      // Удаляем доступ.
      mcms::db()->exec("DELETE FROM `node__access` WHERE `nid` = :nid OR `uid` = :uid", array(':nid' => $node->id, ':uid' => $node->id));

      // Удаление статистики.
      $t = new TableInfo('node__astat');
      if ($t->exists())
        mcms::db()->exec("DELETE FROM `node__astat` WHERE `nid` = :nid", array(':nid' => $node->id));

      break;
    }

    // Обновление структуры сайта.
    if (in_array($node->class, array('domain', 'widget', 'group', 'type'))) {
      $s = new Structure();
      // rebuild() был бы лучше, но он вызывает рекурсию при импорте.
      $s->drop();
    }
  }

  /**
   * Обработка инсталляции модуля.
   *
   * Ничего не делает, просто заглушка — iModuleConfig требует реализации.
   *
   * @todo вынести в BaseModuleSettings.
   *
   * @return void
   */
  public static function hookPostInstall()
  {
  }
};
