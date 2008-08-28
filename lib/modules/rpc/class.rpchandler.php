<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RPCHandler implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    if (null === $ctx) {
      $url = new url(); // bebop_split_url();

      if ('.rpc' === substr($url->path, -4)) {
      }
    }
  }
};
