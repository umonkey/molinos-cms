<?php

class Redirect extends Response
{
  private $url;

  public function __construct($url = '', $code = 302)
  {
    $u = new url($url);
    $url = $u->getAbsolute(Context::last());

    $this->url = $url;

    $message = t('<html><head><title>Redirecting</title>'
      . '<meta http-equiv=\'refresh\' content=\'0; url=@url\' />'
      . '</head><body>'
      . '<h1>Redirecting</h1><p>Redirecting to <a href=\'@url\'>a new location</a>.</p>'
      . '</body></html>', array(
        '@url' => $url,
        ));

    $this->headers[] = 'Location: ' . $this->url;

    parent::__construct($message, 'text/html', $code);
  }

  protected function addHeaders()
  {
    header('Location: ' . $this->url);
  }
}
