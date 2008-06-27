<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if ($ctx->get('action') == 'mass')
      self::doMassAction($ctx);
    else
      self::doSingleAction($ctx);

    if ('POST' == $_SERVER['REQUEST_METHOD'] and $ctx->post('nodeapi_return'))
      $next = $_SERVER['HTTP_REFERER'];
    elseif (null === ($next = $ctx->get('destination')))
      $next = '/';

    mcms::redirect($next);
  }

  private static function doMassAction(RequestContext $ctx)
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

  private static function doSingleAction(RequestContext $ctx, $action = null, $nid = null)
  {
    if (null === $action)
      $action = $ctx->get('action');

    if (null === $nid)
      $nid = $ctx->get('node');

    switch ($action) {
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
      if (!bebop_is_debugger())
        throw new ForbiddenException();

      mcms::debug(Node::load(array(
        'id' => $nid,
        'deleted' => array(0, 1),
        '#recurse' => true,
        )));

      break;

    case 'locate':
      $node = Node::load($nid);

      if ('tag' == $node->class)
        $link = empty($_GET['__cleanurls']) ? '?q=ID' : 'ID/';
      else
        $link = empty($_GET['__cleanurls']) ? '?q=node/ID' : 'node/ID';

      mcms::redirect(str_replace('ID', $node->id, $link));

    case 'reindex':
      $node = Node::load(array('class' => 'type', 'id' => $nid));
      $node->updateTable();
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
      if ('POST' != $_SERVER['REQUEST_METHOD'])
        throw new BadRequestException();

      $parent = $ctx->post('node_content_parent_id');

      $node = Node::create($ctx->get('type'), array(
        'parent_id' => empty($parent) ? null : $parent,
        ));

      $node->formProcess($ctx->post);
      break;

    case 'edit':
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
        $tmp = new NodeExtras();
        $tmp->moveUp($nid);
      }
      break;

    case 'sink':
      if (null === $ctx->get('section')) {
        $tmp = new NodeExtras();
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
};
