<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AutocompleteModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (null !== $ctx->get('source') and null !== $ctx->get('q')) {
      $output = array();
      $parts = explode('.', $ctx->get('source'), 2);

      foreach (Node::find($filter = array('class' => $parts[0], $parts[1] => '%'. $ctx->get('q') .'%'), 10) as $n)
        $output[$n->id] = isset($n->$parts[1]) ? $n->$parts[1] : $n->name;

      bebop_on_json($output);

      throw new ForbiddenException('This request can only be made in JSON mode.');
    }
  }
};
