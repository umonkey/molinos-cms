<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class Node extends NodeBase implements iContentType, iModuleConfig, iNodeHook
{
  // Создаём пустой объект указанного типа, проверяем тип на валидность.
  protected function __construct(array $data = null)
  {
    $this->data = $data;
  }

  // Форматирует документ в соответствии с шаблоном.
  public function render($prefix = null, $theme = null, array $data = null)
  {
    return bebop_render_object("class", $this->class, "all", $this->data);
  }

  // РАБОТА С ФОРМАМИ.

  public function formGetData()
  {
    $user = mcms::user();

    $data = parent::formGetData();

    if ($user->hasAccess('u', 'user'))
      $data['node_access'] = $this->getAccess();

    $data['reset_access'] = 1;
    $data['node_published'] = $this->published;

    return $data;
  }

  public function formProcess(array $data)
  {
    parent::formProcess($data);

    $user = mcms::user();

    if (!empty($data['reset_access'])) {
      if ($user->hasAccess('u', 'user'))
        $this->setAccess(empty($data['node_access']) ? array() : $data['node_access']);
    }

    if ($this->canPublish()) {
      if (empty($data['node_content_published']))
        $this->unpublish();
      elseif (!empty($data['node_content_published']))
        $this->publish();
    }
  }

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
  }

  public static function hookPostInstall()
  {
  }

  public function getDefaultSchema()
  {
    return array(
      'title' => 'Без названия',
      'lang' => 'ru',
      'fields' => array(
        'name' => array(
          'label' => t('Заголовок'),
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'created' => array(
          'label' => t('Дата создания'),
          'type' => 'DateTimeControl',
          'required' => false,
          ),
        'uid' => array(
          'label' => t('Автор'),
          'type' => 'NodeLinkControl',
          'required' => false,
          'values' => 'user.name',
          ),
        ),
      );
  }
};
