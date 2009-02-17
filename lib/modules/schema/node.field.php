<?php

class FieldNode extends Node implements iContentType
{
  public function save()
  {
    parent::checkUnique('name', t('Поле со внутренним именем %name уже есть.', array(
      '%name' => $this->name,
      )));

    // Сбрасываем кэш старых типов.
    $this->flushSchema();

    parent::save();

    // Сбрасываем кэш новых типов.
    $this->flushSchema();

    $this->publish();
    $this->checkIndex();

    return $this;
  }

  private function flushSchema()
  {
    if ($this->id) {
      foreach ($this->getDB()->getResultsV("name", "SELECT `name` FROM `node` WHERE `class` = 'type' AND `id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` = ?)", array($this->id)) as $name)
        Schema::flush($name);
    }
  }

  /**
   * Проверяет и обновляет индекс по этому полю.
   */
  public function checkIndex()
  {
    if ($tableName = $this->checkIndexTable()) {
      $db = $this->getDB();
      $ids = $db->getResultsV("id", "SELECT `id` FROM `node` WHERE `id` NOT IN "
        . "(SELECT `id` FROM `{$tableName}`) AND `class` IN (SELECT `t`.`name` FROM `node` `t` "
        . "INNER JOIN `node__rel` `r` ON `r`.`tid` = `t`.`id` INNER JOIN `node` `f` ON `f`.`id` = `r`.`nid` "
        . "WHERE `f`.`class` = 'field' AND `f`.`name` = ?)", array($this->name));

      if (!empty($ids)) {
        if ($commit = !$db->isTransactionRunning())
          $db->beginTransaction();

        $upd = $db->prepare("INSERT into `{$tableName}` (`id`, `value`) VALUES (?, ?)");

        foreach ($ids as $id) {
          $node = NodeStub::create($id, $db);
          try {
            $upd->execute(array($id, $node->{$this->name}));
          } catch (PDOException $e) {
            mcms::flog(sprintf('not indexing %s/%d: %s', $node->class, $node->id, $e->getMessage()));
          }
        }

        if ($commit)
          $db->commit();
      }
    }
  }

  private function checkIndexTable()
  {
    if (NodeStub::isBasicField($this->name))
      $type = null;
    elseif (empty($this->indexed))
      $type = null;
    elseif (!class_exists($this->type))
      $type = null;
    else {
      $ctl = new $this->type(array(
        '#nocheck' => true,
        ));
      $type = $ctl->getSQL();
    }

    $t = new TableInfo($this->getDB(), $tableName = 'node__idx_' . $this->name);

    if (!$type) {
      if ($t->exists())
        $t->delete();
      return false;
    }

    $t->columnSet('id', array(
      'type' => 'INTEGER',
      'key' => 'pri',
      ));
    $t->columnSet('value', array(
      'type' => $type,
      'required' => !empty($this->required),
      'key' => 'mul',
      ));
    $t->commit();

    return $tableName;
  }

  public function checkPermission($perm)
  {
    return true;
  }

  public function getFormSubmitText()
  {
    return empty($this->type)
      ? t('Продолжить')
      : parent::getFormSubmitText();
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Свойства поля %name', array('%name' => $this->name))
      : t('Добавление нового поля');
  }

  public function getFormFields()
  {
    $fields = array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Внутреннее имя'),
        'required' => true,
        're' => '@^[a-z0-9]+$@',
        'description' => t('Используется внутри системы и в шаблонах. Только латинские буквы и арабские цифры.'),
        'readonly' => !empty($this->id),
        'weight' => 1,
        ),
      'label' => array(
        'type' => 'TextLineControl',
        'label' => t('Отображаемое имя'),
        'required' => true,
        'description' => t('Используется в формах редактирования документов.'),
        'weight' => 2,
        ),
      'type' => array(
        'type' => 'EnumControl',
        'label' => t('Тип'),
        'options' => Control::getKnownTypes(),
        'required' => true,
        'default' => 'TextLineControl',
        'weight' => 3,
        ),
      'description' => array(
        'type' => 'TextAreaControl',
        'label' => t('Подсказка'),
        'description' => t('Помогает пользователю понять, что следует вводить в это поле.'),
        'weight' => 50,
        ),
      'weight' => array(
        'type' => 'NumberControl',
        'label' => t('Вес'),
        'default' => 55,
        'description' => t('Используется для сортировки полей в форме. Чем меньше значение, тем выше поле.'),
        ),
      'required' => array(
        'type' => 'BoolControl',
        'label' => t('Обязательное'),
        'weight' => 60,
        ),
      );

    // Дополнительные настройки.
    if ($this->id) {
      // Запрещаем изменение типа существующего поля.
      $fields['type']['readonly'] = true;

      if (class_exists($this->type)) {
        $tmp = new $this->type(array(
          '#nocheck' => true,
          ));

        if (null !== $tmp->getSQL())
          $fields['indexed'] = array(
            'type' => 'BoolControl',
            'label' => t('Используется для поиска и сортировки'),
            'weight' => 61,
            );

        foreach ((array)$tmp->getExtraSettings() as $k => $v)
          $fields[$k] = $v;
      }
    }

    if (isset($fields['indexed']) and NodeStub::isBasicField($this->name))
      unset($fields['indexed']);

    $fields['types'] = array(
      'type' => 'SetControl',
      'label' => t('Используется типами'),
      'dictionary' => 'type',
      'field' => 'title',
      'group' => t('Типы'),
      'parents' => true,
      );

    return new Schema($fields);
  }

  public function canEditFields()
  {
    return false;
  }

  public function canEditSections()
  {
    return false;
  }

  /**
   * Возвращает false, если поле не может иметь индекса, или его тип.
   * Не могут иметь индекса системные поля и поля, чьи контролы
   * не возвращают getSQL().
   */
  private function getIndexType()
  {
    if (NodeStub::isBasicField($this->name))
      return false;
    if (!class_exists($this->type))
      return false;
    $tmp = new $this->type(array('#nocheck' => true));
    return $tmp->getSQL();
  }
}
