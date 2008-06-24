<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RPCHandler implements iRequestHook
{
  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx) {
      $url = new url(); // bebop_split_url();

      if ('.rpc' === substr($url->path, -4)) {
        $map = mcms::getModuleMap();
        $module = substr($url->path, 0, -4);

        if (array_key_exists($module, $map['modules'])) {
          mcms::db()->beginTransaction();

          if (!empty($map['modules'][$module]['implementors']['iRemoteCall'])) {
            $ctx = RequestContext::getWidget($url->args, $_POST);

            foreach ($map['modules'][$module]['implementors']['iRemoteCall'] as $class) {
              if (mcms::class_exists($class))
                call_user_func_array(array($class, 'hookRemoteCall'), array($ctx));
            }

            mcms::db()->commit();

            header('HTTP/1.1 200 OK');
            header('Content-Type: text/plain; charset=utf-8');
            die('Request not handled.');
          }
        }

        header('HTTP/1.1 404 Not Found');
        header('Content-Type: text/plain; charset=utf-8');
        die('Request handler not found.');
      }
    }
  }
};
