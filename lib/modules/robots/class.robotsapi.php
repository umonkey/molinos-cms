<?php

class RobotsAPI
{
  /**
   * Возвращает содержимое файла для текущего домена.
   * @route GET//robots.txt
   */
  public static function on_get_robots(Context $ctx)
  {
    $content = "";

    foreach ($ctx->registry->poll('ru.molinos.cms.robots.txt', array($ctx)) as $tmp)
      if (!empty($tmp['result']))
        $content .= trim($tmp['result']) . "\n";

    if (!empty($content))
      $content .= "\n";
    $content .= "User-agent: *\n"
      . "Disallow: /admin\n"
      . "Disallow: /api/\n"
      . "Disallow: /doc/\n"
      . "Disallow: /lib/\n"
      . "Disallow: /sites/\n"
      . "Disallow: /download/\n"
      ;

    if (file_exists($path = os::path(MCMS_SITE_FOLDER, 'robots.txt')))
      $content .= trim(file_get_contents($path)) . "\n";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Length: ' . strlen($content));
    die($content);
  }
}
