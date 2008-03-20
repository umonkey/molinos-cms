<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RobotsModule implements iModuleConfig, iRequestHook
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new TextAreaControl(array(
      'value' => 'config_text',
      'label' => t('Содержимое файла'),
      'default' => self::getDefaultRobots(),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx and '/robots.txt' == $_SERVER['REQUEST_URI']) {
      $conf = mcms::modconf('robots');

      $output = empty($conf['text']) ? self::getDefaultRobots() : $conf['text'];

      header('HTTP/1.1 200 OK');
      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: '. strlen($output));
      die($output);
    }
  }

  private static function getDefaultRobots()
  {
    return "User-agent: *\n"
      ."Disallow: /lib/\n"
      ."Disallow: /themes/\n"
      ."Disallow: /tmp/\n"
      ."Disallow: /conf/";
  }
}
