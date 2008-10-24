<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    if ($ctx->get('action') == 'mass')
      $next = self::doMassAction($ctx);
    else
      $next = self::doSingleAction($ctx);

    if (null === $next) {
      if ('POST' == $_SERVER['REQUEST_METHOD'] and $ctx->post('nodeapi_return'))
        $next = $_SERVER['HTTP_REFERER'];
      elseif (null === ($next = $ctx->get('destination')))
        $next = '/';
    }

    mcms::redirect($next);
  }

  private static function doMassAction(Context $ctx)
  {
    if (!empty($_POST['nodes']) and !empty($_POST['action']) and is_array($_POST['action'])) {
      foreach ($_POST['action'] as $action) {
        if (!empty($action)) {
          foreach ($_POST['nodes'] as $nid)
            self::doSingleAction($ctx, $action, $nid);
          break;
        }
      }
    }
  }

  private static function doSingleAction(Context $ctx, $action = null, $nid = null)
  {
    if (null === $action)
      $action = $ctx->get('action');

    if (null === $nid)
      $nid = $ctx->get('node');

    switch ($action) {
    case 'modify':
      $node = Node::load($nid);

      $field = $ctx->get('field');
      $value = $ctx->get('value');

      if (null === $field)
        throw new InvalidArgumentException(t('Не указан параметр field.'));

      if (!$node->checkPermission('u'))
        throw new ForbiddenException();

      $node->$field = $value;
      $node->save();
      break;

    case 'editor':
      $node = Node::load($nid);

      if (!$node->checkPermission('u'))
        throw new ForbiddenException(t('Вам нельзя редактировать этот объект.'));

      $schema = $node->schema();

      if (!array_key_exists($field = $ctx->get('field'), $schema['fields']))
        throw new PageNotFoundException(t('Нет такого поля у этого объекта.'));

      $tpl = $schema['fields'][$field];
      $tpl['value'] = 'node_content_'. $field;
      $tpl['nolabel'] = true;

      $form = new Form(array(
        'action' => '?q=nodeapi.rpc&action=modify&node='. $node->id
          .'&field='. $field,
        ));
      $form->addControl(Control::make($tpl));
      $form->addControl(new SubmitControl(array(
        'text' => 'OK',
        )));

      die($form->getHTML($node->formGetData()));

    case 'revert':
      $info = mcms::db()->getResults("SELECT `v`.`nid` AS `id`, "
        ."`n`.`class` AS `class` FROM `node__rev` `v` "
        ."INNER JOIN `node` `n` ON `n`.`id` = `v`.`nid` "
        ."WHERE `v`.`rid` = ?", array($rid = $ctx->get('rid')));

      if (!empty($info)) {
        mcms::user()->checkAccess('u', $info[0]['class']);
        mcms::db()->exec("UPDATE `node` SET `rid` = ? WHERE `id` = ?",
          array($rid, $info[0]['id']));
        mcms::flush();
      }

      break;

    case 'dump':
      $filter = array(
        'id' => $nid,
        'deleted' => array(0),
        '#cache' => false,
        '#recurse' => empty($_GET['bare']) ? 1 : 0,
        );

      if (bebop_is_debugger())
        $filter['deleted'][] = 1;

      $node = Node::load($filter);

      bebop_on_json(array(
        'node' => $node->getRaw(),
        ));

      if (!empty($_GET['a']))
        $node = $node->getRaw();
      mcms::debug($node);

      throw new ForbiddenException();

    case 'locate':
      $node = Node::load($nid);

      if ('tag' == $node->class)
        $link = '?q=ID';
      else
        $link = '?q=node/ID';

      mcms::redirect(str_replace('ID', $node->id, $link));

    case 'reindex':
      $node = Node::load(array('id' => $nid, '#recurse' => 1));
      mcms::user()->checkAccess('u', $node->class);

      if ($node->class == 'type')
        $node->updateTable();
      else
        $node->reindex();

      break;

    case 'publish':
    case 'enable':
      if (null !== $nid) {
        $node = Node::load($nid);
        $node->publish();
      }
      break;

    case 'unpublish':
    case 'disable':
      if (null !== $nid) {
        $node = Node::load($nid);
        $node->unpublish();
      }
      break;

    case 'delete':
      if (null !== $nid) {
        $node = Node::load($nid);
        $node->delete();
      }
      break;

    case 'clone':
      $node = Node::load(array(
        'id' => $nid,
        'deleted' => array(0, 1),
        ));
      $node->duplicate();
      break;

    case 'create':
      if (!$ctx->method('post'))
        throw new BadRequestException(t('Этот запрос можно отправить '
          .'только методом POST.'));

      $parent = $ctx->post('node_content_parent_id');

      $node = Node::create($ctx->get('type'), array(
        'parent_id' => empty($parent) ? null : $parent,
        ));

      $node->formProcess($ctx->post);

      if ($ctx->method('post'))
        if (!($next = $ctx->post('destination')))
          $next = $ctx->get('destination', '/');
      else
        $next = $ctx->get('destination', '/');

      mcms::redirect(self::fixredir($next, $node));
      break;

    case 'edit':
      if (!$ctx->method('post'))
        throw new BadRequestException(t('Этот запрос можно отправить '
          .'только методом POST.'));
      $node = Node::load($ctx->get('node'));
      $node->formProcess($ctx->post);
      break;

    case 'undelete':
      $node = Node::load(array(
        'id' => $nid,
        'deleted' => 1,
        ));
      $node->undelete();
      break;

    case 'erase':
      try {
        $node = Node::load(array(
          'id' => $nid,
          'deleted' => 1,
          ));
        $node->erase();
      } catch (ObjectNotFoundException $e) {
        // случается при рекурсивном удалении вложенных объектов
      }
      break;

    case 'raise':
      if (null === $ctx->get('section')) {
        $tmp = new NodeMover(mcms::db());
        $tmp->moveUp($nid);
      }
      break;

    case 'sink':
      if (null === $ctx->get('section')) {
        $tmp = new NodeMover(mcms::db());
        $tmp->moveDown($nid);
      }
      break;

    default:
      mcms::debug($ctx, $_POST);
    }

    bebop_on_json(array(
      'action' => $action,
      'node' => $nid,
      'status' => 'ok',
      ));
  }

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

    return strval($url);
  }
};
