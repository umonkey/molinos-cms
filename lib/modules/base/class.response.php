<?php

class Response
{
  private $code;
  private $type;
  private $content;

  public function __construct($code, $content = null, $type = 'text/html')
  {
    $this->code = $code;
    $this->type = $type;
    $this->content = $content;
  }

  public function send()
  {
    header('HTTP/1.1 ' . $this->code . ' ' . $this->getResponseTitle());
    header('Content-Type: ' . $this->type . '; charset=utf-8');
    header(sprintf('Content-Length: %u', null === $this->content ? 0 : strlen($this->content)));
    die($this->content);
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
}
