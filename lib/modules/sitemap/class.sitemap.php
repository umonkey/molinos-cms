<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Sitemap
{
  /**
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function hookNodeUpdate(Context $ctx, Node $node, $op)
  {
    $stop = array(
      'create',
      'delete',
      'restore',
      'publish',
      'unpublish',
      'update',
      );

    if (!in_array($op, $stop))
      return;

    if (!empty($node->class)) {
      $conf = (array)$ctx->config->get('modules/sitemap');

      if (in_array($node->class, (array)$ctx->config->get('modules/sitemap/send_types'))) {
        if (file_exists($path = self::get_file_path($ctx)))
          unlink($path);

        if (empty($conf['no_ping'])) {
          if (count($hosts = explode("\n", $conf['ping']))) {
            $sm = 'http://'. MCMS_HOST_NAME . mcms::path() . '/';
            $sm .= empty($_GET['__cleanurls'])
              ? '?q=sitemap.rpc'
              : 'sitemap.rpc';

            foreach ($hosts as $host) {
              mcms::flog('pinging '. $host .' with '. $sm);
              http::fetch('http://'. $host .'/ping?sitemap='. urlencode($sm), http::CONTENT | http::NO_CACHE);
            }
          }
        }
      }
    }
  }

  public static function hookRemoteCall(Context $ctx)
  {
    $path = self::get_file_path($ctx);

    if (!is_readable($path))
      self::write($ctx, $path);

    if ($f = fopen($path, 'r')) {
      header('HTTP/1.1 200 OK');
      header('Content-Type: text/xml; charset=utf-8');
      header('Content-Length: '. filesize($path));
      die(fpassthru($f));
    } else {
      throw new RuntimeException(t('Не удалось сформировать файл.'));
    }
  }

  private static function write(Context $ctx, $filename)
  {
    $f = fopen($filename . '.tmp', 'w');

    fwrite($f, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
    fwrite($f, "<?xml-stylesheet href=\"http://" . url::host() . mcms::path()
      ."/lib/modules/sitemap/sitemap.xsl\" type=\"text/xsl\" media=\"screen\"?>\n");
    fwrite($f, "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");

    self::write_sections($ctx, $f);
    self::write_nodes($ctx, $f);

    fwrite($f, "</urlset>\n");
    fclose($f);

    rename($filename . '.tmp', $filename);
  }

  private static function write_sections(Context $ctx, $f)
  {
    $res = $ctx->db->getResultsV('id', "SELECT `n`.`id` AS `id` "
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

  private static function write_nodes(Context $ctx, $f)
  {
    $filter = array(
      'class' => (array)$ctx->config->get('modules/sitemap/send_types'),
      'published' => 1,
      'deleted' => 0,
      );

    if (empty($filter))
      throw new PageNotFoundException(t('Карта сайта не настроена.'));

    if (count($nodes = Node::find($ctx->db, $filter))) {
      fwrite($f, "<!-- documents -->\n");

      foreach ($nodes as $node) {
        $line = "<url>"
          ."<loc>http://" . MCMS_HOST_NAME . "/node/{$node->id}</loc>";
        if (!empty($node->updated)) {
          $date = gmdate('Y-m-d', strtotime($node->updated));
          $line .= "<lastmod>{$date}</lastmod>";
        }
        $line .= "</url>\n";
        fwrite($f, $line);
      }
    }
  }

  private static function get_file_path(Context $ctx)
  {
    return os::path($ctx->config->getPath('main/tmpdir'), 'sitemap-' . MCMS_HOST_NAME . '.xml');
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.sitemap
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'ping' => array(
        'type' => 'TextAreaControl',
        'label' => t('Уведомлять поисковые серверы'),
        'default' => "www.google.com",
        'description' => t('Не все серверы поддерживают уведомления, не надо добавлять всё подряд!'),
        'weight' => 1,
        'group' => t('Серверы'),
        ),
      'no_ping' => array(
        'type' => 'BoolControl',
        'label' => t('Не надо никого уведомлять'),
        'description' => t('Отправка уведомлений производится при каждом '
          .'добавлении или удалении документа, что может тормозить работу. '
          .'Гораздо лучше явно <a href="@url">сказать поисковым серверам</a>, '
          .'где следует брать карту сайта.', array(
            '@url' => 'http://www.google.com/webmasters/sitemaps/',
            )),
        'weight' => 2,
        'group' => t('Серверы'),
        ),
      'send_types' => array(
        'type' => 'SetControl',
        'label' => t('Сообщать о документах типов'),
        'options' => Node::getSortedList('type', 'title', 'name'),
        'weight' => 3,
        'group' => t('Типы доокументов'),
        'store' => true,
        ),
      ));
  }
}
