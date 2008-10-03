<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AutocompleteModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    if (null !== $ctx->get('source') and null !== $ctx->get('search')) {
      $output = array();
      $parts = explode('.', $ctx->get('source'), 2);

      foreach (Node::find($filter = array('class' => $parts[0], $parts[1] => '%'. $ctx->get('search') .'%'), 10) as $n)
        $output[$n->id] = isset($n->$parts[1]) ? $n->$parts[1] : $n->name;

      $output = join("\n", array_values($output));

      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: '. strlen($output));
      die($output);
    }
  }
};
