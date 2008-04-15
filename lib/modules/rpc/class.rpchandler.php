<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RPCHandler implements iRequestHook
{
  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx) {
      $url = bebop_split_url();

      if ('.rpc' === substr($url['path'], -4)) {
        $map = mcms::getModuleMap();
        $module = substr($url['path'], 1, -4);

        if (array_key_exists($module, $map['modules'])) {
          mcms::db()->beginTransaction();

          if (!empty($map['modules'][$module]['implementors']['iRemoteCall'])) {
            $ctx = RequestContext::getWidget(isset($url['args']) ? $url['args'] : array(), $_POST);

            try {
              foreach ($map['modules'][$module]['implementors']['iRemoteCall'] as $class) {
                if (mcms::class_exists($class))
                  call_user_func_array(array($class, 'hookRemoteCall'), array($ctx));
              }
            } catch (UserErrorException $e) {
              mcms::db()->rollback();

              header("HTTP/1.1 {$e->getCode()} Error");
              header('Content-Type: text/plain; charset=utf-8');

              printf("%s: %s\n", $e->getMessage(), $e->getNote());

              if (bebop_is_debugger()) {
                print "\n--- стэк вызова (виден только разработчикам) ---\n";
                print $e->getTraceAsString();
              }

              die();
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
