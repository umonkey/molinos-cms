<?php

class JSONResponse extends Response
{
  public function __construct(array $content)
  {
    $this->code = array_key_exists('code', $content)
      ? intval($content['code'])
      : 200;
    $this->type = 'application/json';
    $this->content = array_key_exists('content', $content)
      ? $content['content']
      : null;
  }

  protected function addHeaders()
  {
    header('Expires: ' . date('r', time() - (60*60*24)));
  }

  protected function getContent()
  {
    $locale = setlocale(LC_ALL, "en_US.UTF-8");
    $output = json_encode($this->content);
    setlocale(LC_ALL, $locale);
    return $output;
  }
}
