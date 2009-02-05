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

    // $output .= '<p>Request: http://' . $_SERVER['HTTP_HOST'] . mcms::path() . '/' . mcms_plain($this->url()->string(true)) . '</p>';

    $output .= html::em('div', array('class' => 'hidden cdata'));

    $output .= $this->getSqlLogHTML($this->ctx);

    $output .= '<hr/>' . mcms::getSignature($this->ctx, true);

    $output .= '</body></html>';

    return new Response($output);
  }

  /**
   * Рисует лог SQL запросов.
   */
  private function getSqlLogHTML()
  {
    if (!isset($this->ctx->db))
      return;

    if (null === ($log = $this->ctx->db->getLog()))
      return;

    $log = '<h2>Запросы к БД (' . count($log) . ')</h2>'
      . '<ol><li>' . join('</li><li>', $log) . '</li></ol>';

    $log = preg_replace('/[\r\n]+\s+/', '<br/>', $log);

    return $log;
  }
}
