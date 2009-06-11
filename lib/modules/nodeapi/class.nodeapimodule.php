<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule extends RPCHandler
{
  public static function hookRemoteCall(Context $ctx)
  {
    if ($commit = $ctx->method('post'))
      $ctx->db->beginTransaction();
    $next = parent::hookRemoteCall($ctx, __CLASS__);
    if ($commit)
      $ctx->db->commit();

    if ($next instanceof Response)
      return $next;

    if (null === $next or true === $next)
      return $ctx->getRedirect();

    return new Redirect($next);
  }

  /**
   * Изменение отдельного поля документа.
   */
  public static function rpc_post_modify(Context $ctx)
  {
    $field = $ctx->get('field');
    $value = $ctx->get('value');

    if (null === $field)
      throw new InvalidArgumentException(t('Не указан параметр field.'));

    $node = Node::load($ctx->get('node'))->knock('u');

    $node->$field = $value;
    $node->save($ctx->db);
  }

  /**
   * Получение формы редактирования объекта.
   */
  public static function rpc_get_editor(Context $ctx)
  {
    $node = Node::load($ctx->get('node'))->knock('u');

    $schema = $node->getSchema();

    if (!array_key_exists($field = $ctx->get('field'), $schema['fields']))
      throw new PageNotFoundException(t('Нет такого поля у этого объекта.'));

    $tpl = $schema['fields'][$field];
    $tpl['value'] = $field;
    $tpl['nolabel'] = true;

    $form = new Form(array(
      'action' => '?q=nodeapi.rpc&action=modify&node='. $node->id
        .'&field='. $field,
      ));
    $form->addControl(Control::make($tpl));
    $form->addControl(new SubmitControl(array(
      'text' => 'OK',
      )));

    return new Response($form->getHTML($node));
  }

  /**
   * Вывод содержимого объекта.
   */
  public static function on_get_dump(Context $ctx)
  {
    $filter = array(
      'id' => $ctx->get('node'),
      );

    if (!$ctx->canDebug())
      $filter['deleted'] = 0;

    if ($ctx->get('raw')) {
      $node = Node::load($filter, $ctx->db);
      $temp = $node->{'never should this field exist'};
      mcms::debug($node);
    } else {
      $xml = Node::findXML($filter, $ctx->db);
      if (empty($xml))
        throw new RuntimeExteption(t('Для этого документа нет XML представления (такого быть не должно), см. <a href="@url">сырой вариант</a>.', array(
          '@url' => '?q=node/' . $filter['id'] . '/dump&raw=1',
          )));
      $res = new Response('<?xml version="1.0"?>' . $xml, 'text/xml');
      $res->send();
    }

    throw new ForbiddenException();
  }

  /**
   * Поиск объекта на сайте.
   */
  public static function on_locate(Context $ctx)
  {
    if (!($nid = $ctx->get('node')))
      throw new BadRequestException(t('Не указан идентификатор объекта (GET-параметр node).'));

    $map = BaseRoute::load($ctx);

    foreach ($map as $k => $v)
      if (false === strpos($k, '*'))
        unset($map[$k]);

    if (empty($map))
      throw new RuntimeException(t('Не удалось найти маршрут, пригодный для отображения документа.'));

    // Выбираем первый маршрут.
    list($path) = array_keys($map);

    // Заменяем звезду на код документа.
    $path = str_replace('*', $ctx->get('node'), $path);

    // Заменяем localhost на имя текущего домена.
    if (0 === strpos($path, 'localhost/'))
      $path = MCMS_HOST_NAME . '/' . substr($path, 10);

    // Формируем полноценный путь.
    $path = 'http://' . $path;

    // Готово.
    $ctx->redirect($path, Redirect::OTHER);
  }

  /**
   * Публикация объектов.
   */
  public static function rpc_post_publish(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $nodle)
      $node->knock('p')->publish()->save();
    $ctx->db->commit();
  }

  /**
   * Сокрытие объектов.
   */
  public static function rpc_post_unpublish(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('p')->unpublish()->save();
    $ctx->db->commit();
  }

  /**
   * Удаление объектов.
   */
  public static function rpc_post_delete(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('d')->delete()->save();
    $ctx->db->commit();
    return $ctx->getRedirect();
  }

  /**
   * Клонирование объектов.
   */
  public static function rpc_post_clone(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('c')->duplicate();
    $ctx->db->commit();
  }

  /**
   * Создание объекта.
   */
  public static function rpc_post_create(Context $ctx)
  {
    $parent = $ctx->post('parent_id');
    $node = Node::create(array(
      'class' => $ctx->get('type'),
      'parent_id' => empty($parent) ? null : $parent,
      ))->knock('c');
    $node->formProcess($ctx->post)->save($ctx->db);
    $next = $ctx->post('destination', $ctx->get('destination', ''));

    return new Redirect(self::fixredir($next, $node));
  }

  /**
   * Изменение объекта.
   */
  public static function rpc_post_edit(Context $ctx)
  {
    $node = Node::load($ctx->get('node'), $ctx->db)->knock('u');
    if (null === $node->uid and $node->isNew())
      $node->uid = $ctx->user->id;
    $node->formProcess($ctx->post, $ctx->get('field'))->save($ctx->db);
  }

  /**
   * Восстановление удалённых объектов.
   */
  public static function rpc_post_undelete(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('d')->undelete();
    $ctx->db->commit();
  }

  /**
   * Окончательное удаление из корзины.
   */
  public static function rpc_post_erase(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('d')->erase();
    $ctx->db->commit();
  }

  /**
   * Перемещение наверх по дереву.
   */
  public static function rpc_get_raise(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node) {
      $node->knock('u');
      $tmp = new NodeMover($ctx->db);
      $tmp->moveUp($node->id);
    }
    $ctx->db->commit();
  }

  /**
   * Перемещение вниз по дереву.
   */
  public static function rpc_get_sink(Context $ctx)
  {
    if (null === $ctx->get('section')) {
      $ctx->db->beginTransaction();
      $tmp = new NodeMover($ctx->db);
      $tmp->moveDown($ctx->get('node'));
      $ctx->db->commit();
    }
  }

  protected static function rpc_post_touch(Context $ctx)
  {
    $params = array();
    $sql = "SELECT `id` FROM `node` WHERE `deleted` = 0 AND `class` IN (SELECT `name` FROM `node` WHERE `deleted` = 0 AND `id` " . sql::in($ctx->post('nodes'), $params) . ")";
    $ids = $ctx->db->getResultsV("id", $sql, $params);

    foreach ($ids as $id) {
      $node = Node::load($id);
      $node->touch();
      $node->save();
    }
  }

  /**
   * Подставляет в адрес редиректа информацию о модифицированном объекте.
   */
  public static function fixredir($path, Node $node, $updated = false)
  {
    if ($updated)
      $mode = 'updated';
    elseif ($node->published)
      $mode = 'created';
    else
      $mode = 'pending';

    $url = new url($path);
    $url->setarg('created', null);
    $url->setarg('updated', null);
    $url->setarg('pending', null);

    if ('%ID' == $url->arg('id')) {
      $url->setarg('id', $node->id);
    } else {
      $url->setarg($mode, $node->id);
      $url->setarg('type', $node->class);
    }

    return $url->string();
  }

  /**
   * Снятие публикации с объектов.
   */
  public static function on_unpublish(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('p')->unpublish()->save();
    $ctx->db->commit();
    return $ctx->getRedirect();
  }

  /**
   * Публикация объектов.
   */
  public static function on_publish(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('p')->publish()->save();
    $ctx->db->commit();
    return $ctx->getRedirect();
  }

  /**
   * Удаление объектов.
   */
  public static function on_delete(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('d')->delete()->save();
    $ctx->db->commit();

    return $ctx->getRedirect();
  }

  public static function on_post_sendto(Context $ctx)
  {
    if ($pick = $ctx->post('selected')) {
      if (false === strpos($ctx->post('sendto'), '.'))
        list($nid, $fieldName) = array($ctx->post('sendto'), null);
      else
        list($nid, $fieldName) = explode('.', $ctx->post('sendto'));

      $params = array($fieldName);
      $sql = "REPLACE INTO `node__rel` (`tid`, `nid`, `key`) SELECT %ID%, `id`, ? FROM `node` WHERE `deleted` = 0 AND `id` " . sql::in($pick, $params);

      $ctx->db->beginTransaction();
      $node = Node::load($nid)->knock('u')->onSave($sql, $params)->save();
      $ctx->db->commit();

      // destiantion сейчас указывает на список, а нам надо
      // вернуться на уровень выше.
      $url = new url($ctx->get('destination'));
      if ($next = $url->arg('destination'))
        $ctx->redirect($next);
    }

    return $ctx->getRedirect();
  }

  public static function on_get_refresh(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (self::getNodes($ctx) as $node)
      $node->knock('u')->touch()->save();
    $ctx->db->commit();
    return $ctx->getRedirect();
  }

  /**
   * Подтверждение удаления объектов.
   */
  public static function on_get_delete(Context $ctx)
  {
    $types = Node::getSortedList('type', 'title', 'name', 'name', 'name', 'name');

    $nodes = Node::find(array(
      'id' => explode(' ', $ctx->get('node')),
      'deleted' => 0,
      ), $ctx->db);

    if (empty($nodes))
      throw new PageNotFoundException();

    $result = '';
    foreach ($nodes as $node) {
      if ($node->checkPermission('d'))
        $result .= html::em('node', array(
          'id' => $node->id,
          'name' => $node->getName(),
          'type' => $types[$node->class],
          ));
    }

    if (empty($result))
      throw new ForbiddenException();

    return html::em('content', array(
      'name' => 'confirmdelete',
      'title' => t('Подтвердите удаление объектов'),
      ), $result);
  }

  /**
   * Возвращает обрабатываемые ноды.
   * Для запросов методом POST использует массив selected[],
   * для запросов методом GET — параметр node.
   */
  private static function getNodes(Context $ctx)
  {
    if ($ctx->method('post'))
      $ids = $ctx->post('selected', array());
    else
      $ids = explode(' ', $ctx->get('node'));

    if (empty($ids))
      throw new BadRequestException(t('Не указаны идентификаторы документов (POST-массив selected[] или GET-параметр node)'));

    return Node::find(array(
      'id' => $ids,
      ), $ctx->db);
  }
};
