<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    $nid = $ctx->get('node');

    if ($ctx->get('action') == 'mass')
      self::doMassAction($ctx);
    elseif (null !== ($nid = $ctx->get('node')))
      self::doSingleAction($ctx->get('action'), $nid);

    if (null !== ($next = $ctx->get('destination')))
      bebop_redirect($next);
  }

  private static function doMassAction(RequestContext $ctx)
  {
    if (!empty($_POST['document_list_selected']) and !empty($_POST['document_list_mass']) and is_array($_POST['document_list_mass'])) {
      foreach ($_POST['document_list_mass'] as $action) {
        if (!empty($action)) {
          foreach ($_POST['document_list_selected'] as $nid)
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
    }
  }
};
