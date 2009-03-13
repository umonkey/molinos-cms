<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AutocompleteModule
{
  /**
   * @mcms_message ru.molinos.cms.rpc.autocomplete
   */
  public static function hookRemoteCall(Context $ctx)
  {
    if (null !== $ctx->get('source') and null !== $ctx->get('search')) {
      $output = array();
      $parts = explode('.', $ctx->get('source'), 2);

      foreach (Node::find($filter = array('class' => $parts[0], $parts[1] => '%'. $ctx->get('search') .'%'), 10) as $n)
        $output[$n->id] = isset($n->$parts[1]) ? $n->$parts[1] : $n->name;

      $output = join("\n", array_values($output));

      return new Response($output, 'text/plain');
    }
  }
};
