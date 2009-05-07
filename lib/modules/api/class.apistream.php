<?php

class APIStream
{
  const debug = 1;

  private static $router = null;

  private $position = 0;
  private $data;

  function stream_open($path, $mode, $options, &$opened_path)
  {
    if (null === self::$router) {
      mcms::flog('API not initialized: router not set.');
      return false;
    }

    if (is_array($url = parse_url($path)) and 'localhost' == $url['host']) {
      if (isset($url['path']) and 0 === strpos($url['path'], '/api/')) {
        $realpath = substr($url['path'], 1);
        if (isset($url['query']))
          $realpath .= '?' . $url['query'];

        try {
          $time = microtime(true);
          $ctx = new Context(array(
            'url' => $realpath,
            ));
          $this->data = self::$router->dispatch($ctx)->dump();
          mcms::flog(sprintf('API OK %f %s', microtime(true) - $time, $path));
        } catch (Exception $e) {
          mcms::flog('Stream error: ' . $e->getMessage());
          return false;
        }

        return true;
      }
    }

    mcms::flog('API call failed: ' . $path);
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
    if (!stream_wrapper_register('cms', __CLASS__))
      throw new RuntimeException(t('Не удалось инициализировать обработчик cms://'));
    else {
      self::$router = new Router();
      self::$router->poll($ctx);
    }
  }
}
