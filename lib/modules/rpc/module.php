<?php

class RPCHandler implements iRequestHook
{
  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx) {
      $url = bebop_split_url();

      if ('.rpc' === substr($url['path'], -4)) {
        $map = bebop_get_module_map();

        if (array_key_exists($module = substr($url['path'], 1, -4), $map)) {
          if (!empty($map[$module]['interface']['iRemoteCall'])) {
            $ctx = RequestContext::getWidget(isset($url['args']) ? $url['args'] : array());

            try {
              foreach ($map[$module]['interface']['iRemoteCall'] as $class) {
                if (class_exists($class))
                  call_user_func_array(array($class, 'hookRemoteCall'), array($ctx));
              }
            } catch (UserErrorException $e) {
              header("HTTP/1.1 {$e->getCode()} Error");
              header('Content-Type: text/plain; charset=utf-8');
              die($e->getMessage());
            }

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
