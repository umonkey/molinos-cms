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
      Logger::log($message = 'API not initialized: router not set.', 'api');

    elseif (false !== strpos($mode, 'w'))
      Logger::log($message = 'XML API is read only.', 'api');

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
          Logger::log(sprintf('OK %f %s', microtime(true) - $time, substr($path, 16)), 'api');
        } catch (Exception $e) {
          Logger::log(sprintf('ERROR %s', substr($path, 16)), 'api');
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
    Logger::log('stream op not handled: ' . $name, 'api');
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
      Logger::log("IN 0.000000 " . substr(MCMS_REQUEST_URI, 1), 'api');
    }
  }

  /**
   * Возвращает префикс для обращения к XML API из шаблонов.
   */
  public static function getPrefix()
  {
    return 'cms://localhost/api/';
  }
}
