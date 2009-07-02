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

      if (in_array($node->class, (array)$ctx->config->get('modules/sitemap/send_types')))
        if (file_exists($path = self::get_file_path($ctx)))
          unlink($path);
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
    fwrite($f, "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n");

    self::write_root($ctx, $f);
    self::write_nodes($ctx, $f);

    fwrite($f, "</urlset>\n");
    fclose($f);

    rename($filename . '.tmp', $filename);
  }

  private static function write_nodes(Context $ctx, $f)
  {
    $filter = array(
      'class' => $ctx->db->getResultsV("name", "SELECT `name` FROM `node` WHERE `class` = 'type' AND `deleted` = 0 AND `published` = 1"),
      'published' => 1,
      'deleted' => 0,
      '#sort' => '-id',
      );

    list($sql, $params) = Query::build($filter)->getSelect(array('id', 'updated'));
    if ($nodes = $ctx->db->getResults($sql, $params)) {
      fwrite($f, "<!-- documents -->\n");

      foreach ($nodes as $node) {
        $line = "<url>"
          ."<loc>http://" . MCMS_HOST_NAME . "/node/{$node['id']}</loc>";
        if (!empty($node['updated'])) {
          $date = gmdate('c', strtotime($node['updated']));
          $line .= "<lastmod>{$date}</lastmod>";
        }
        $line .= "</url>\n";
        fwrite($f, $line);
      }
    }
  }

  private static function write_root(Context $ctx, $f)
  {
    $types = $ctx->db->getResultsV("name", "SELECT `name` FROM `node` WHERE `class` = 'type' AND `deleted` = 0 AND `published` = 1");

    $params = array();
    $max = $ctx->db->fetch("SELECT MAX(updated) FROM `node` WHERE `published` = 1 AND `deleted` = 0 AND `class` " . sql::in($types, $params), $params);

    $line = "<!-- root -->\n<url>"
      ."<loc>http://" . MCMS_HOST_NAME . "/</loc>";
    $date = gmdate('c', strtotime($max));
    $line .= '<changefreq>hourly</changefreq>';
    $line .= "<lastmod>{$date}</lastmod>";
    $line .= "</url>\n";
    fwrite($f, $line);
  }

  private static function get_file_path(Context $ctx)
  {
    return os::path($ctx->config->getPath('main/tmpdir'), 'sitemap-' . MCMS_HOST_NAME . '.xml');
  }

  /**
   * Добавление в robots.txt
   * @mcms_message ru.molinos.cms.robots.txt
   */
  public static function on_get_robots(Context $ctx)
  {
    return 'Sitemap: ' . $ctx->url()->getBase($ctx) . 'sitemap.xml';
  }
}
