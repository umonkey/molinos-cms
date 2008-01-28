<?php

class TagNode extends Node implements iContentType
{
  public function save($clear = true, $forcedrev = null)
  {
    parent::save($clear, $forcedrev);
  }

  // Возвращает список существующих разделов, в виде плоского списка
  // с элементом depth, для рендеринга в виде дерева.
  public static function getTags($mode, array $options = array())
  {
    $result = array();

    // Загружаем все корневые разделы (в нормальных условиях такой должен быть один,
    // но на случае ошибок в БД мы всё таки даём возможность работать с ошибочными
    // разделами).
    foreach (Node::find(array('class' => 'tag', 'parent_id' => null)) as $node) {
      if ($mode == 'select') {
        foreach ($node->getChildren($mode, $options) as $k => $v)
          $result[$k] = $v;
       } else {
        $result = array_merge($result, $node->getChildren($mode, $options));
       }
    }

    return $result;
  }

  // Сохранение фиксированных прав.
  public function setAccess(array $perms, $reset = true)
  {
    $perms['Structure Managers'] = array('c', 'r', 'u', 'd');
    return parent::setAccess($perms, $reset);
  }

  // Проверка прав на объект.  Менеджеры структуры всегда всё могут.
  public function checkPermission($perm)
  {
    if (AuthCore::getInstance()->getUser()->hasGroup('Structure Managers'))
      return true;
    return NodeBase::checkPermission($perm);
  }

  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    $form->title = (null === $this->id)
      ? t('Добавление нового раздела')
      : t('Редактирование раздела "%name"', array('%name' => $this->name));

    return $form;
  }

  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id) {
      $data['Visitors']['r'] = 1;
      $data['Structure Managers']['r'] = 1;
      $data['Structure Managers']['u'] = 1;
      $data['Structure Managers']['d'] = 1;
    }

    return $data;
  }
};

// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:
