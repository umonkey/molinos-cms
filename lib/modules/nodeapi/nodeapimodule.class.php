<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if ($ctx->get('action') == 'mass')
      self::doMassAction($ctx);
    elseif (null !== ($nid = $ctx->get('node')))
      self::doSingleAction($ctx->get('action'), $nid);

    if (null !== ($next = $ctx->get('destination')))
      bebop_redirect($next);
  }

  private static function doMassAction(RequestContext $ctx)
  {
    if (!empty($_POST['nodes']) and !empty($_POST['action']) and is_array($_POST['action'])) {
      foreach ($_POST['action'] as $action) {
        if (!empty($action)) {
          foreach ($_POST['nodes'] as $nid)
            self::doSingleAction($action, $nid);
          break;
        }
      }
    }
  }

  private static function doSingleAction($action, $nid)
  {
    switch ($action) {
    case 'publish':
      if (null !== $nid) {
        $node = Node::load($nid);
        $node->publish();
      }
      break;

    case 'unpublish':
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

    default:
      bebop_debug($action, $nid, $_POST);
    }
  }
};
