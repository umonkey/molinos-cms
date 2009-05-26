<?php

class SchemaMenu
{
  public static function on_get_types(Context $ctx)
  {
    $tmp = new SchemaList($ctx);
    return $tmp->getHTML('schema', array(
      '#raw' => true,
      'nosearch' => true,
      'create' => 'admin/create/type',
      ));
  }

  public static function on_get_type_fields(Context $ctx, $path, array $pathinfo, $node)
  {
    mcms::debug($node);
  }

  /**
   * Список полей для конкретного типа.
   */
  public static function on_list_fields(Context $ctx)
  {
    $type = $ctx->get('type');

    $node = Node::load(array(
      'class' => 'type',
      'name' => $type,
      'deleted' => 0,
      ), $ctx->db);

    if (empty($node->fields)) {
      if ($node->getObject()->backportLinkedFields()) {
        $ctx->db->beginTransaction();
        $node->save();
        $ctx->db->commit();
      }
    }

    $types = '';
    foreach (Control::getKnownTypes() as $k => $v)
      if (!empty($v))
        $types .= html::em('type', array(
          'name' => $k,
          'title' => $v,
          ));

    return html::em('content', array(
      'name' => 'typefields',
      'title' => t('%type: поля', array(
        '%type' => $node->title,
        )),
      'base' => 'admin/structure/fields',
      ), $node->getXML() . html::wrap('types', $types));
  }

  /**
   * Возвращает форму редактирования конкретного поля.
   */
  public static function on_get_edit_field(Context $ctx)
  {
    $type = Node::load(array(
      'class' => 'type',
      'name' => $ctx->get('type'),
      'deleted' => 0,
      ), $ctx->db);
    $type->getObject()->backportLinkedFields();

    if (!isset($type->fields[$fieldName = $ctx->get('field')]))
      throw new PageNotFoundException();
    $field = $type->fields[$fieldName];

    $schema = self::getFieldSchema($field['type']);

    $form = $schema->getForm(array(
      'title' => t('Поле: %type / %field', array(
        '%type' => $type->title,
        '%field' => mb_strtolower($field['label']),
        )),
      ));
    $form->addControl(new BoolControl(array(
      'value' => 'delete',
      'label' => t('Удалить это поле'),
      )));
    $form->addControl(new SubmitControl(array(
      'text' => t('Сохранить'),
      )));

    $formXML = $form->getXML(Control::data(array('name' => $fieldName) + $field));

    return html::em('content', array(
      'name' => 'edit',
      ), $formXML);
  }

  /**
   * Обрабатывает форму редактирования поля.
   */
  public static function on_post_edit_field(Context $ctx)
  {
    $type = Node::load(array(
      'class' => 'type',
      'name' => $ctx->get('type'),
      'deleted' => 0,
      ), $ctx->db);

    $type->getObject()->backportLinkedFields();

    if (!array_key_exists($fieldName = $ctx->get('field'), $type->fields))
      throw new PageNotFoundException();
    $field = $type->fields[$fieldName];

    // Удаление.
    if ($ctx->post('delete')) {
      $fields = $type->fields;
      unset($fields[$fieldName]);
      $type->fields = $fields;
    }

    // Модификация
    else {
      $schema = self::getFieldSchema($field['type']);
      $data = $schema->getFormData($ctx);
      $data->type = $field['type'];

      $data = $data->dump();
      foreach ($data as $k => $v)
        if (empty($v))
          unset($data[$k]);

      $fields = $type->fields;
      $fields[$data['name']] = $data;
      unset($fields[$data['name']]['name']);

      // Переименовали поле, удаляем старое.
      if ($data['name'] != $fieldName)
        unset($fields[$fieldName]);

      $type->fields = $fields;
    }

    $ctx->db->beginTransaction();
    $type->save();
    $ctx->db->commit();

    return $ctx->getRedirect();
  }

  /**
   * Возвращает форму добавления поля.
   */
  public static function on_get_add_field(Context $ctx)
  {
    $schema = self::getFieldSchema($class = $ctx->get('class'));

    $types = Control::getKnownTypes();

    $form = $schema->getForm(array(
      'title' => t('Новое поле: %type', array(
        '%type' => mb_strtolower($types[$class]),
        )),
      ));
    $form->addControl(new SubmitControl(array(
      'text' => t('Добавить'),
      )));

    return html::em('content', array(
      'name' => 'edit',
      ), $form->getXML(Control::data()));
  }

  /**
   * Обрабатывает форму добавления поля.
   */
  public static function on_post_add_field(Context $ctx)
  {
    $type = Node::load(array(
      'class' => 'type',
      'name' => $ctx->get('type'),
      'deleted' => 0,
      ), $ctx->db);

    $data = self::getFieldSchema($ctx->get('class'))->getFormData($ctx)->dump();

    if (array_key_exists($data['name'], $type))
      throw new ForbiddenException(t('Такое поле уже есть.'));

    $name = $data['name'];
    unset($data['name']);

    // Нормализовываем и сохраняем тип поля.
    $class = $ctx->get('class');
    $tmp = new $class(array('value' => 'tmp'));
    $data['type'] = get_class($tmp);

    $fields = $type->fields;
    $fields[$name] = $data;
    $type->fields = $fields;

    $ctx->db->beginTransaction();
    $type->save();
    $ctx->db->commit();

    $ctx->redirect('admin/structure/fields?type=' . $type->name);
  }

  private static function getFieldSchema($className = null)
  {
    $schema = array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Внутреннее имя'),
        'required' => true,
        're' => '@^[a-z0-9]+$@',
        'description' => t('Используется внутри системы и в шаблонах. Только латинские буквы и арабские цифры.'),
        'weight' => 1,
        ),
      'label' => array(
        'type' => 'TextLineControl',
        'label' => t('Отображаемое имя'),
        'description' => t('Используется в формах редактирования документов.'),
        'weight' => 2,
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
        'description' => t('Используется для сортировки полей'),
        'weight' => 55,
        'default' => 55,
        'required' => true,
        ),
      'required' => array(
        'type' => 'BoolControl',
        'label' => t('Обязательное'),
        'weight' => 60,
        ),
      'group' => array(
        'type' => 'TextLineControl',
        'label' => t('Группа'),
        'weight' => 70,
        'description' => t('Используется для группировки полей.'),
        ),
      );

    if (class_exists($className)) {
      $ctl = new $className(array(
        'value' => 'tmp',
        ));
      $schema = array_merge($schema, $ctl->getExtraSettings());
    }

    return new Schema($schema);
  }

  /**
   * Пересохранение всех документов определённого типа.
   */
  public static function on_refresh_type(Context $ctx)
  {
    $tmp = Node::create($type = $ctx->get('type'), array(), $ctx->db);

    if (!$tmp->checkPermission('u'))
      throw new ForbiddenException();

    $sth = $ctx->db->prepare("SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0");
    $sth->execute(array($type));

    $ctx->db->beginTransaction();

    while ($id = $sth->fetchColumn(0))
      Node::load($id, $ctx->db)->refresh()->save();

    $ctx->db->commit();

    return $ctx->getRedirect();
  }
}
