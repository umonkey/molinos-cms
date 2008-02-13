<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AutocompleteModule implements iRequestHook
{
  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx) {
      $url = bebop_split_url();

      if ('/autocomplete.rpc' === $url['path']) {
        $output = array();

        if (!empty($url['args']['source']) and !empty($url['args']['q'])) {
          $parts = explode('.', $url['args']['source'], 2);

          foreach (Node::find($filter = array('class' => $parts[0], $parts[1] => '%'. $url['args']['q'] .'%'), 10) as $n)
            $output[$n->id] = isset($n->$parts[1]) ? $n->$parts[1] : $n->name;
        }

        die(bebop_on_json($output));
      }
    }
  }
};
