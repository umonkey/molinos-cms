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

    $output .= $this->getWidgets($widgetresults);

    $output .= mcms::html('div', array('class' => 'hidden cdata'));

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

  /**
   * Рисует таблицу с информацией о виджетах.
   */
  private function getWidgets(array $widgetresults = null)
  {
    if ($widgets = mcms::profile('get')) {
      ksort($widgets);

      $s = new Structure();
      $output = "<h2>Виджеты на этой странице (" . count($widgets) . ")</h2><table class='profile'>"
        . '<tr><th colspan=\'4\'>Виджет</th><th>Время</th><th>SQL</th><th>&nbsp;</th></tr>';

      $totaltime = 0;
      $totalsql = 0;

      foreach ($widgets as $name => $v) {
        $w = array_shift($s->findWidgets(array($name)));

        $plink = '?q=' . $this->ctx->query() . '&widget=' . $name
          . '&debug=profile&nocache=' . $this->ctx->get('nocache');

        $dlink = '?q=' . $this->ctx->query() . '&widget=' . $name
          . '&debug=widget&nocache=' . $this->ctx->get('nocache');

        $output .= '<tr>';
        $output .= mcms::html('td', array('align' => 'left'), l($plink, $name));
        $output .= mcms::html('td', $w ? l('http://code.google.com/p/molinos-cms/wiki/' . $w['class'], $w['class']) : null);
        $output .= mcms::html('td', $w['id'] ? l('?q=admin/content/edit/' . $w['id'] . '&destination=CURRENT', 'настройки') : null);
        $output .= mcms::html('td', l($dlink, 'debug'));
        $output .= mcms::html('td', array('align' => 'left'), $v['time']);
        $output .= mcms::html('td', array('align' => 'right'), $v['queries']);

        if (null === $widgetresults or !array_key_exists($name, $widgetresults) or empty($widgetresults[$name])) {
          $output .= mcms::html('td');
        } else {
          $cdata = mcms::html('span', array('class' => 'hidden'), htmlspecialchars($widgetresults[$name]));
          $link = mcms::html('u', 'результат');
          $output .= mcms::html('td', $link . $cdata);
        }

        $output .= '</tr>';

        $totaltime += $v['time'];
        $totalsql += $v['queries'];
      }

      $output .= "<tr><th colspan='4'>&nbsp;</th><th align='left'>{$totaltime}</th><th align='right'>{$totalsql}</th></tr>"
        . '</table>';
    }

    return $output;
  }
}
