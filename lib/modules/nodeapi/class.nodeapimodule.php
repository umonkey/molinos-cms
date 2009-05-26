<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule extends RPCHandler
{
  public static function hookRemoteCall(Context $ctx)
  {
    try {
      if ($commit = $ctx->method('post'))
        $ctx->db->beginTransaction();
      $next = parent::hookRemoteCall($ctx, __CLASS__);
      if ($commit)
        $ctx->db->commit();
    } catch (Exception $e) {
      mcms::fatal($e);
    }

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
    $node = Node::load($ctx->get('node'));

    $field = $ctx->get('field');
    $value = $ctx->get('value');

    if (null === $field)
      throw new InvalidArgumentException(t('Не указан параметр field.'));

    if (!$node->checkPermission('u'))
      throw new ForbiddenException();

    $node->$field = $value;
    $node->save($ctx->db);
  }

  /**
   * Получение формы редактирования объекта.
   */
  public static function rpc_get_editor(Context $ctx)
  {
    $node = Node::load($ctx->get('node'));

    if (!$node->checkPermission('u'))
      throw new ForbiddenException(t('Вам нельзя редактировать этот объект.'));

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
      if ($ctx->get('fresh'))
        $xml = Node::load($filter)->refresh()->getXML();
      else
        $xml = Node::findXML($ctx->db, $filter);
      if (empty($xml))
        mcms::fatal(t('Для этого документа нет XML представления (такого быть не должно), см. <a href="@url">сырой вариант</a>.', array(
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
  public static function rpc_get_locate(Context $ctx)
  {
    $node = Node::load($ctx->get('node'));

    if ('tag' == $node->class)
      $link = '?q=ID';
    else
      $link = '?q=node/ID';

    if ($ctx->get('__cleanurls'))
      $link = substr($link, 3);

    return new Redirect(str_replace('ID', $node->id, $link));
  }

  /**
   * Ренидексация объекта (используется?)
   */
  public static function rpc_get_reindex(Context $ctx)
  {
    $node = Node::load(array('id' => $ctx->get('node'), '#recurse' => 1));
    $ctx->user->checkAccess('u', $node->class);

    if ($node->class == 'type')
      $node->updateTable();
    else
      $node->reindex();
  }

  /**
   * Публикация объектов.
   */
  public static function rpc_post_publish(Context $ctx)
  {
    foreach (self::getNodes($ctx) as $nid) {
      $node = Node::load($nid);
      $node->publish();
    }
  }

  /**
   * Сокрытие объектов.
   */
  public static function rpc_post_unpublish(Context $ctx)
  {
    foreach (self::getNodes($ctx) as $nid) {
      $node = Node::load($nid);
      $node->unpublish();
    }
  }

  /**
   * Удаление объектов.
   */
  public static function rpc_post_delete(Context $ctx)
  {
    foreach (self::getNodes($ctx) as $nid) {
      $node = Node::load($nid);
      $node->delete();
    }
  }

  /**
   * Клонирование объектов.
   */
  public static function rpc_post_clone(Context $ctx)
  {
    foreach (self::getNodes($ctx) as $nid)
      $node = Node::load($nid)->duplicate();
  }

  /**
   * Создание объекта.
   */
  public static function rpc_post_create(Context $ctx)
  {
    $parent = $ctx->post('parent_id');
    $node = Node::create($ctx->get('type'), array(
      'parent_id' => empty($parent) ? null : $parent,
      ));
    $node->formProcess($ctx->post)->save($ctx->db);
    $next = $ctx->post('destination', $ctx->get('destination', ''));

    return new Redirect(self::fixredir($next, $node));
  }

  /**
   * Изменение объекта.
   */
  public static function rpc_post_edit(Context $ctx)
  {
    $node = Node::load($ctx->get('node'), $ctx->db)->getObject();
    if (null === $node->uid and $node->isNew())
      $node->uid = $ctx->user->id;
    $node->formProcess($ctx->post, $ctx->get('field'))->save($ctx->db);
  }

  /**
   * Восстановление удалённых объектов.
   */
  public static function rpc_post_undelete(Context $ctx)
  {
    $nodes = Node::find($ctx->db, array(
      'id' => self::getNodes($ctx),
      'deleted' => 1,
      ));

    foreach ($nodes as $node)
      $node->undelete();
  }

  /**
   * Окончательное удаление из корзины.
   */
  public static function rpc_post_erase(Context $ctx)
  {
    $nodes = Node::find($ctx->db, array(
      'id' => self::getNodes($ctx),
      'deleted' => 1,
      ));

    foreach ($nodes as $node) {
      try {
        $node->erase();
      } catch (ObjectNotFoundException $e) {
        // случается при рекурсивном удалении вложенных объектов
      }
    }
  }

  /**
   * Перемещение наверх по дереву.
   */
  public static function rpc_get_raise(Context $ctx)
  {
    if (null === $ctx->get('section')) {
      $ctx->db->beginTransaction();
      $tmp = new NodeMover($ctx->db);
      $tmp->moveUp($ctx->get('node'));
      $ctx->db->commit();
    }
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
   * Возвращает идентификаторы задействованных объектов.
   */
  private static function getNodes(Context $ctx)
  {
    if (!is_array($nodes = $ctx->post('nodes', array($ctx->get('node')))))
      $nodes = array();
    return $nodes;
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
    if ($nodes = $ctx->post('selected', array())) {
      $ctx->db->beginTransaction();
      foreach ($nodes as $node)
        Node::load($node)->unpublish()->save();
      $ctx->db->commit();
    }
    return $ctx->getRedirect();
  }

  /**
   * Публикация объектов.
   */
  public static function on_publish(Context $ctx)
  {
    if ($nodes = $ctx->post('selected', array())) {
      $ctx->db->beginTransaction();
      foreach ($nodes as $node)
        Node::load($node)->publish()->save();
      $ctx->db->commit();
    }
    return $ctx->getRedirect();
  }

  /**
   * Удаление объектов.
   */
  public static function on_delete(Context $ctx)
  {
    if ($nodes = $ctx->post('selected', array())) {
      $ctx->db->beginTransaction();
      foreach ($nodes as $node)
        Node::load($node, $ctx->db)->delete();
      $ctx->db->commit();
    } else {
      throw new BadRequestException(t('Не указаны документы для удаления (массив selected).'));
    }
    return $ctx->getRedirect();
  }

  public static function on_post_sendto(Context $ctx)
  {
    if ($pick = $ctx->post('selected')) {
      $ctx->db->beginTransaction();

      $pick = Node::load(array(
        'id' => $pick,
        'deleted' => 0,
        ));

      list($nid, $fieldName) = explode('.', $ctx->post('sendto'));

      $node = Node::load($nid);
      if (!$node->checkPermission('u'))
        throw new ForbiddenException();
      $node->$fieldName = $pick;
      $node->save();

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
    $node = Node::load($ctx->get('id'), $ctx->db);
    if (!$node->checkPermission('u'))
      throw new ForbiddenException();
    $ctx->db->beginTransaction();
    $node->touch()->save();
    $ctx->db->commit();
    return $ctx->getRedirect();
  }

  /**
   * Подтверждение удаления объектов.
   */
  public static function on_get_delete(Context $ctx)
  {
    $types = Node::getSortedList('type', 'title', 'name', 'name', 'name', 'name');

    $nodes = Node::find($ctx->db, array(
      'id' => explode(' ', $ctx->get('node')),
      'deleted' => 0,
      ));

    if (empty($nodes))
      throw new PageNotFoundException();

    $result = '';
    foreach ($nodes as $node) {
      $node = $node->getObject();
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
};
