<?php

class Debugger
{
  private $ctx;

  public function __construct(Context $ctx)
  {
    $this->ctx = $ctx;
  }

  public function getProfile(array $widgetresults = null)
  {
    $output = '<html><head>'
      . '<title>Molinos CMS Profiler</title>'
      . '<script type=\'text/javascript\' src=\'themes/all/jquery/jquery.js\'></script>'
      . '<script type=\'text/javascript\' src=\'lib/modules/base/class.debugger.js\'></script>'
      . '<link rel=\'stylesheet\' type=\'text/css\' href=\'lib/modules/base/class.debugger.css\' />'
      . '</head>'
      . '<body><h1>Molinos CMS Profiler</h1>';

    // $output .= '<p>Request: http://' . $_SERVER['HTTP_HOST'] . mcms::path() . '/' . html::plain($this->url()->string(true)) . '</p>';

    $output .= html::em('div', array('class' => 'hidden cdata'));

    $output .= '<hr/>' . mcms::getSignature($this->ctx, true);

    $output .= '</body></html>';

    return new Response($output);
  }
}
