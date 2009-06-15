<?php

class APIStream
{
  const debug = 1;

  private static $router = null;

  private $position = 0;
  private $data;

  function stream_open($path, $mode, $options, &$opened_path)
  {
    if (null === self::$router)
      mcms::flog($message = 'API not initialized: router not set.');

    elseif (false !== strpos($mode, 'w'))
      mcms::flog($message = 'XML API is read only.');

    elseif (is_array($url = parse_url($path)) and 'localhost' == $url['host']) {
      if (isset($url['path']) and 0 === strpos($url['path'], '/api/')) {
        $realpath = substr($url['path'], 1);
        if (isset($url['query']))
          $realpath .= '?' . $url['query'];

        try {
          $time = microtime(true);
          $ctx = Context::yesIKnowThisIsStrangeButIWantANewInstance(array(
            'url' => $realpath,
            ));
          if ($tmp = self::$router->dispatch($ctx))
            $this->data = $tmp->dump();
          else
            $this->data = html::em('error');
          mcms::flog(sprintf('API OK %f %s', microtime(true) - $time, substr($path, 16)));
        } catch (Exception $e) {
          mcms::flog(sprintf('API ERROR %s', substr($path, 16)));
          $this->data = html::em('error', array(
            'type' => get_class($e),
            'message' => $e->getMessage(),
            ));
        }

        if ($options & STREAM_USE_PATH)
          $opened_path = $path;

        return true;
      }
    }

    if ($options & STREAM_REPORT_ERRORS)
      trigger_error($message, E_WARNING);

    return false;
  }

  function stream_read($count)
  {
    $data = substr($this->data, $this->position, $count);
    $this->position += $count;
    return $data;
  }

  function stream_tell()
  {
    return $this->position;
  }

  function stream_eof()
  {
    return $this->position > strlen($this->data);
  }

  function stream_seek($offset, $whence)
  {
  }

  public function stream_close()
  {
  }

  public function stream_flush()
  {
    return false;
  }

  public function url_stat()
  {
    return array(
      'size' => strlen($this->data),
      );
  }

  public function __call($name, array $args)
  {
    mcms::flog('stream op not handled: ' . $name);
  }

  public static function init(Context $ctx)
  {
    if (in_array('cms', stream_get_wrappers()))
      ;
    elseif (!stream_wrapper_register('cms', __CLASS__))
      throw new RuntimeException(t('Не удалось инициализировать обработчик cms://'));
    else {
      self::$router = new Router();
      self::$router->poll($ctx);
    }
  }
}
