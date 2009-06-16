<?php

class RobotsAPI
{
  /**
   * Настройка маршрутов.
   * @mcms_message ru.molinos.cms.route.poll
   */
  public static function on_route_poll()
  {
    return array(
      'GET//robots.txt' => array(
        'call' => __CLASS__ . '::on_get_robots'
        ),
      );
  }

  /**
   * Возвращает содержимое файла для текущего домена.
   */
  public static function on_get_robots(Context $ctx)
  {
    $content = "User-agent: *\nDisallow: /lib\nDisallow: /sites\nDisallow: /doc\n";

    if (file_exists($path = os::path(MCMS_SITE_FOLDER, 'robots.txt')))
      $content .= trim(file_get_contents($path)) . "\n";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Length: ' . strlen($content));
    die($content);
  }
}
