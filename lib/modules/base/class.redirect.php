<?php

class Redirect extends Response
{
  private $url;

  public function __construct($url = '', $code = 302)
  {
    $u = new url($url);
    $url = $u->getAbsolute(Context::last());

    $this->url = $url;

    parent::__construct($code, 'Please go to ' . $url . '.', 'text/plain');
  }

  public function send()
  {
    header('Location: ' . $this->url);
    parent::send();
  }
}
