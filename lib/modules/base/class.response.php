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
    if ($this->isJSON()) {
      header('HTTP/1.1 200 OK');
      header('Content-Type: application/x-json');

      setlocale(LC_ALL, "en_US.UTF-8");

      $content = json_encode(array(
        'code' => $this->code,
        'type' => $this->type,
        'content' => $this->content,
        ));
    } else {
      header('HTTP/1.1 ' . $this->code . ' ' . $this->getResponseTitle());
      header('Content-Type: ' . $this->type . '; charset=utf-8');

      $this->addHeaders();

      $content = $this->content;
    }

    header(sprintf('Content-Length: %u', null === $this->content ? 0 : strlen($this->content)));

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
    case 401:
      return 'Not Authorized';
    case 403:
      return 'Forbidden';
    case 404:
      return 'Not Found';
    case 500:
      return 'Internal Server Error';
    default:
      return 'Unknown Response';
    }
  }

  protected function addHeaders()
  {
  }

  private function isJSON()
  {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }
}
