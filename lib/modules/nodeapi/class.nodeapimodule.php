<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    try {
      $next = mcms::dispatch_rpc(__CLASS__, $ctx);
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
    $node->save();
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
   * Откат документа на нужную версию.
   */
  public static function rpc_get_revert(Context $ctx)
  {
    $info = $ctx->db->getResults("SELECT `v`.`nid` AS `id`, "
      ."`n`.`class` AS `class` FROM `node__rev` `v` "
      ."INNER JOIN `node` `n` ON `n`.`id` = `v`.`nid` "
      ."WHERE `v`.`rid` = ?", array($rid = $ctx->get('rid')));

    if (!empty($info)) {
      $ctx->user->checkAccess('u', $info[0]['class']);
      $ctx->db->exec("UPDATE `node` SET `rid` = ? WHERE `id` = ?",
        array($rid, $info[0]['id']));
      mcms::flush();
    }
  }

  /**
   * Вывод содержимого объекта.
   */
  public static function rpc_get_dump(Context $ctx)
  {
    $filter = array(
      'id' => $ctx->get('node'),
      'deleted' => array(0),
      '#recurse' => $ctx->get('bare') ? 0 : 1,
      );

    if ($ctx->canDebug())
      $filter['deleted'][] = 1;

    $node = Node::load($filter);

    mcms::debug(array(
      'node' => $node,
      'links' => $node->getActionLinks(),
      ));

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
    foreach (self::getNodes($ctx) as $nid) {
      $node = Node::load(array(
        'id' => $nid,
        'deleted' => array(0, 1),
        ));
      $node->duplicate();
    }
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

    $node->formProcess($ctx->post)->save();

    $next = $ctx->post('destination', $ctx->get('destination', ''));

    return new Redirect(self::fixredir($next, $node));
  }

  /**
   * Изменение объекта.
   */
  public static function rpc_post_edit(Context $ctx)
  {
    $node = Node::load($ctx->get('node'));
    $node->formProcess($ctx->post)->save();
  }

  /**
   * Восстановление удалённых объектов.
   */
  public static function rpc_post_undelete(Context $ctx)
  {
    $nodes = Node::find(array(
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
    $nodes = Node::find(array(
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
      $tmp = new NodeMover($ctx->db);
      $tmp->moveUp($ctx->get('node'));
    }
  }

  /**
   * Перемещение вниз по дереву.
   */
  public static function rpc_get_sink(Context $ctx)
  {
    if (null === $ctx->get('section')) {
      $tmp = new NodeMover($ctx->db);
      $tmp->moveDown($ctx->get('node'));
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

    if ('%ID' == $url->arg('id')) {
      $url->setarg('id', $node->id);
    } else {
      $url->setarg($mode, $node->id);
      $url->setarg('type', $node->class);
    }

    return $url->string();
  }
};
