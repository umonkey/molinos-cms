<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Sitemap implements iModuleConfig, iRemoteCall, iNodeHook
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new TextAreaControl(array(
      'value' => 'config_ping',
      'label' => t('Уведомлять поисковые серверы'),
      'default' => "www.google.com",
      'description' => t('Не все серверы поддерживают уведомления, не надо добавлять всё подряд!'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_no_ping',
      'label' => t('Не надо никого уведомлять'),
      'description' => t('Отправка уведомлений производится при каждом '
        .'добавлении или удалении документа, что может тормозить работу. '
        .'Гораздо лучше явно <a href="@url">сказать поисковым серверам</a>, '
        .'где следует брать карту сайта.', array(
          '@url' => 'http://www.google.com/webmasters/sitemaps/',
          )),
      )));
    $form->addControl(new SetControl(array(
      'value' => 'config_skip_types',
      'label' => t('Игнорировать документы типов'),
      'options' => self::get_possible_types(),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookNodeUpdate(Node $node, $op)
  {
    $stop = array(
      'create',
      'delete',
      'restore',
      'publish',
      'unpublish',
      );

    if (!in_array($op, $stop))
      return;

    if (!empty($node->class)) {
      $conf = (array)mcms::modconf('sitemap');

      if (!array_key_exists('skip_types', $conf))
        $conf['skip_types'] = array();

      if (!in_array($node->class, $conf['skip_types'])) {
        if (file_exists($path = self::get_file_path()))
          unlink($path);

        if (empty($conf['no_ping'])) {
          if (count($hosts = explode("\n", $conf['ping']))) {
            $sm = 'http://'. url::host() . mcms::path() .'/?q=sitemap.rpc';

            foreach ($hosts as $host) {
              mcms::flog('sitemap', 'pinging '. $host .' with '. $sm);
              http::fetch('http://'. $host .'/ping?sitemap='. urlencode($sm), http::CONTENT | http::NO_CACHE);
            }
          }
        }
      }
    }
  }

  public static function hookRemoteCall(Context $ctx)
  {
    $path = self::get_file_path();

    if (!is_readable($path))
      self::write($path);

    if ($f = fopen($path, 'r')) {
      header('HTTP/1.1 200 OK');
      header('Content-Type: text/xml; charset=utf-8');
      header('Content-Length: '. filesize($path));
      die(fpassthru($f));
    } else {
      throw new RuntimeException(t('Не удалось сформировать файл.'));
    }
  }

  private static function write($filename)
  {
    $f = fopen($filename, 'w');

    fwrite($f, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
    fwrite($f, "<?xml-stylesheet href=\"http://" . url::host() . mcms::path()
      ."/lib/modules/sitemap/sitemap.xsl\" type=\"text/xsl\" media=\"screen\"?>\n");
    fwrite($f, "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");

    self::write_sections($f);
    self::write_nodes($f);

    fwrite($f, "</urlset>\n");
    fclose($f);
  }

  private static function write_sections($f)
  {
    $res = mcms::db()->getResultsV('id', "SELECT `n`.`id` AS `id` "
      ."FROM `node` `n` "
      ."WHERE `n`.`deleted` = 0 AND `n`.`published` = 1 "
      ."AND `n`.`class` = 'tag' AND `n`.`id` IN "
      ."(SELECT `tid` FROM `node__rel`)");

    if (!empty($res)) {
      fwrite($f, "<!-- sections -->\n");

      foreach ($res as $id)
        fwrite($f, "<url><loc>http://" . url::host() . "/{$id}</loc></url>\n");
    }
  }

  private static function write_nodes($f)
  {
    $conf = mcms::modconf('sitemap');

    $filter = array(
      '-class' => $conf['skip_types'],
      'published' => 1,
      'deleted' => 0,
      );

    if (count($nodes = Node::find($filter))) {
      fwrite($f, "<!-- documents -->\n");

      foreach ($nodes as $node) {
        $line = "<url>"
          ."<loc>http://" . url::host() . "/node/{$node->id}</loc>";
        if (!empty($node->updated)) {
          $date = gmdate('Y-m-d', strtotime($node->updated));
          $line .= "<lastmod>{$date}</lastmod>";
        }
        $line .= "</url>\n";
        fwrite($f, $line);
      }
    }
  }

  private static function get_file_path()
  {
    return mcms::config('tmpdir') . DIRECTORY_SEPARATOR . 'sitemap-' .  url::host() .'.xml';
  }

  private static function get_disallowed_types()
  {
    $conf = mcms::modconf('sitemap');

    $types = empty($conf['skip_types'])
      ? array()
      : $conf['skip_types'];

    foreach (self::get_hard_skip_types() as $skip)
      if (!in_array($skip, $types))
        $types[] = $skip;

    return $types;
  }

  private static function get_hard_skip_types()
  {
    return array('domain', 'file', 'group', 'moduleinfo', 'tag', 'widget', 'type');
  }

  private static function get_possible_types()
  {
    $result = array();
    $skip = self::get_hard_skip_types();

    foreach (Node::getSortedList('type', 'title', 'name') as $k => $v)
      if (!in_array($k, $skip))
        $result[$k] = $v;

    return $result;
  }
}
