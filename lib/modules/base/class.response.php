<?php

class Response
{
  private $code;
  private $type;
  private $content;

  public function __construct($content, $type = 'text/html', $code = 200)
  {
    $this->code = $code;
    $this->type = $type;
    $this->content = $content;
  }

  public function send()
  {
    if (headers_sent())
      die(t('<br/>Вывод страницы невозможен: заголовки уже ушли.'));

    // Сбрасываем кэш, если просили.
    mcms::flush(mcms::FLUSH_NOW);

    // Возвращаем JSON.
    if ($this->isJSON()) {
      header('HTTP/1.1 200 OK');
      header('Content-Type: application/x-json; charset=utf-8');
      header('Expires: ' . date('r', time() - (60*60*24)));

      setlocale(LC_ALL, "en_US.UTF-8");

      $content = json_encode(array(
        'code' => $this->code,
        'type' => $this->type,
        'content' => $this->getContent(),
        ));
    }

    // Возвращаем обычный результат.
    else {
      header('HTTP/1.1 ' . $this->code . ' ' . $this->getResponseTitle());
      header('Content-Type: ' . $this->type . '; charset=utf-8');

      $this->addHeaders();

      $content = $this->getContent();
    }

    header(sprintf('Content-Length: %u', (null === $content) ? 0 : strlen($content)));

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

  private function isJSON()
  {
    if (!function_exists('json_encode'))
      return false;

    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']))
      return false;

    if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest'))
      return false;

    return true;
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

      // $content = str_replace('<head>', '<head><meta name="generator" content="Molinos CMS v' . mcms::version() . '" />', $content);
    }

    return $content;

  }
}
