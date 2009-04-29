<?php

class Response
{
  protected $code;
  protected $type;
  protected $content;

  private $cache = null;
  private $ckey = null;
  private $ttl;

  public function __construct($content, $type = 'text/html', $code = 200)
  {
    $this->code = $code;
    $this->type = $type;
    $this->content = $content;
  }

  public function setCache(cache $cache, $ckey, $ttl = 60)
  {
    $this->cache = $cache;
    $this->ckey = $ckey;
    $this->ttl = $ttl;
  }

  public function send()
  {
    if (headers_sent())
      die(t('<br/>Вывод страницы невозможен: заголовки уже ушли.'));

    // Закрываем транзакцию, если есть.
    if ($ctx = Context::last()) {
      if (isset($ctx->db)) {
        try {
          $ctx->db->commit();
        }
        catch (NotConnectedException $e) { }

        // Если мы дошли до отправки данных пользователю,
        // вряд ли мы не проинсталлированы. Хотя хорошо бы
        // разобраться, как мы сюда вообще попадаем.
        catch (NotInstalledException $e) { }

        mcms::flush(mcms::FLUSH_NOW);
      }
    }

    // Сбрасываем кэш, если просили.
    mcms::flush(mcms::FLUSH_NOW);

    header('HTTP/1.1 ' . $this->code . ' ' . $this->getResponseTitle());
    header('Content-Type: ' . $this->type . '; charset=utf-8');

    $this->addHeaders();

    $content = $this->getContent();
    $length = strlen($content);

    if ($this->cache and $this->ckey and $this->ttl) {
      $store = array(
        'code' => $this->code,
        'text' => $this->getResponseTitle(),
        'type' => $this->type . '; charset=utf-8',
        'length' => $length,
        'content' => $content,
        'expires' => time() + $this->ttl,
        );
      $this->cache->{$this->ckey} = $store;
    }

    header(sprintf('Content-Length: %u', $length));

    die($content);
  }

  private function getResponseTitle()
  {
    switch ($this->code) {
    case 200:
      return 'OK';
    case 301:
      return 'Moved Permanently';
    case 302:
      return 'Found';
    case 303:
      return 'See Other';
    case 304:
      return 'Not Modified';
    case 307:
      return 'Temporary Redirect';
    case 400:
      return 'Bad Request';
    case 401:
      return 'Not Authorized';
    case 403:
      return 'Forbidden';
    case 404:
      return 'Not Found';
    case 415:
      return 'Bad Media Type';
    case 500:
      return 'Internal Server Error';
    case 503:
      return 'Service Unavailable';
    default:
      return 'Unknown Response';
    }
  }

  protected function addHeaders()
  {
  }

  /**
   * Возвращает текст страницы.
   */
  protected function getContent()
  {
    $content = $this->content;

    if ('text/html' == $this->type) {
      $content = str_replace(
        array(
          '$request_time',
          '$peak_memory',
          ),
        array(
          microtime(true) - MCMS_START_TIME,
          mcms::filesize(memory_get_peak_usage()),
          ),
        $content);

      if (!empty($_GET['__cleanurls'])) {
        $re = '@(href|src|action)=([\'"])\?q=([^&"\']+)\&amp\;+@';
        $content = preg_replace($re, '\1=\2\3?', $content);

        $re = '@(href|src|action)=([\'"])\?q=([^&"\']+)([\'"])+@';
        $content = preg_replace($re, '\1=\2\3\4', $content);
      }
    }

    return $content;

  }
}
