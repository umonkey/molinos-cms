<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeApiModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    $nid = $ctx->get('node');

    switch ($ctx->get('action')) {
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
    }

    if (null !== ($next = $ctx->get('destination')))
      bebop_redirect($next);
  }
};
