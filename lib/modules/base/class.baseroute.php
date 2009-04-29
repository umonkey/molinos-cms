<?php

class BaseRoute
{
  public static function serve(Context $ctx, $query, array $handler, $param = null)
  {
    $content = '';
    $page = array(
      'status' => 200,
      'name' => self::getNameFromQuery($query),
      'title' => null,
      'host' => MCMS_HOST_NAME,
      'peer' => $_SERVER['REMOTE_ADDR'],
      'back' => urlencode($_SERVER['REQUEST_URI']),
      'prefix' => null,
      'base' => $ctx->url()->getBase($ctx),
      'version' => MCMS_VERSION,
      );

    if (isset($handler['title']))
      $page['title'] = $handler['title'];

    $theme = empty($handler['theme'])
      ? 'default'
      : $handler['theme'];

    if (null !== $ctx->get('theme') and $ctx->canDebug())
      $theme = $ctx->get('theme');

    $page['prefix'] = MCMS_SITE_FOLDER . '/themes/' . $theme;

    try {
      $content .= self::renderWidgets($ctx, $handler, $param);
    } catch (UserErrorException $e) {
      $content = '';
      $page['status'] = 500;
      $page['error'] = get_class($e);
      $page['title'] = $e->getMessage();
    } catch (Exception $e) {
      $content = '';
      $page['status'] = 500;
      $page['error'] = get_class($e);
      $page['title'] = $e->getMessage();
    }

    if (defined('MCMS_START_TIME'))
      $page['time'] = microtime(true) - MCMS_START_TIME;
    $xml = html::em('page', $page, $content);

    $xsl = self::findStyleSheet($theme, $page['name']);

    $type = empty($handler['content_type'])
      ? 'text/html'
      : $handler['content_type'];

    return xslt::transform($xml, $xsl, $type);
  }

  /**
   * Обрабатывает виджеты, возвращает результат в XML.
   */
  private static function renderWidgets(Context $ctx, array $pathinfo, $param = null)
  {
    $params = self::getWidgetParams($ctx, $pathinfo, $param);

    $ctx->registry->broadcast('ru.molinos.cms.hook.request.before', array($ctx));

    $content = html::wrap('request', self::getWidgetParamsXML($params) . self::getGetParams($ctx));

    if (!empty($pathinfo['widgets'])) {
      $count = 0;
      $time = microtime(true);

      $tmp = '';
      $want = $ctx->get('widget');
      $widgets = Widget::loadWidgets($ctx);
      foreach (explode(',', $pathinfo['widgets']) as $wname) {
        if (null !== $want and $want != $wname)
          continue;
        if (array_key_exists($wname, $widgets) and empty($widgets[$wname]['disabled'])) {
          if (null !== ($widget = Widget::getInstance($wname, $widgets[$wname]))) {
            $wxml = $widget->render($ctx, $params);
            if ($wname == $ctx->get('widget')) {
              $r = new Response('<?xml version="1.0"?>' . $wxml, 'text/xml');
              $r->send();
            }
            $tmp .= $wxml;
            $count++;
          }
        }
      }

      $content .= html::wrap('widgets', $tmp, array(
        'count' => $count,
        'time' => microtime(true) - $time,
        ));
    }

    return $content;
  }

  /**
   * Возвращает информацию об объектах, относящихся к запрошенной странице.
   */
  private static function getWidgetParams(Context $ctx, array $pathinfo, $param)
  {
    $defaultsection = isset($pathinfo['defaultsection'])
      ? $pathinfo['defaultsection']
      : null;

    $ids = array();
    if (null !== $param)
      $ids[] = $param;
    if (null !== $defaultsection)
      $ids[] = $defaultsection;

    $params = array();

    $where = "n.id " . sql::in($ids, $params);

    if (null !== $param) {
      $where .= ' OR n.id IN (SELECT tid FROM node__rel WHERE nid = ?)';
      $params[] = $param;
    }

    $sql = "SELECT id, class, xml FROM node n WHERE n.deleted = 0 AND n.published = 1 AND ({$where})";

    $data = $ctx->db->getResultsK("id", $sql, $params);

    $result = array(
      'document' => null,
      'section' => null,
      'root' => null,
      );

    // Проверяем явно запрошенный объект.
    if (null !== $param and isset($data[$param])) {
      if ('tag' != $data[$param]['class'])
        $result['document'] = $data[$param];
      else
        $result['section'] = $data[$param];
    }

    // Используем запрошенный вручную раздел.
    if (null !== $result['document'] and $want = $ctx->get('section')) {
      if (!isset($data[$want]) or 'tag' != $data[$want]['class'])
        throw new PageNotFoundException();
      $result['section'] = $data[$want];
    }

    // Определяем раздел для документа.
    if (null !== $result['document'] and null === $result['section']) {
      foreach ($data as $node) {
        if ('tag' == $node['class'] and $node['id'] != $defaultsection) {
          $result['section'] = $node;
          break;
        }
      }
    }

    // Раздел по умолчанию для страницы.
    if (null !== $defaultsection and isset($data[$defaultsection])) {
      $tmp = $data[$defaultsection];
      if ('tag' == $tmp['class']) {
        $result['root'] = $tmp;
        if (null === $result['section'])
          $result['section'] = $tmp;
      }
    }

    return $result;
  }

  private static function getWidgetParamsXML(array $params)
  {
    $result = '';
    foreach ($params as $k => $v)
      if (!empty($v['xml']))
        $result .= html::em($k, $v['xml']);
    return $result;
  }

  private static function getGetParams(Context $ctx)
  {
    $result = '';

    foreach ($ctx->url()->args as $k => $v)
      if (!is_array($v) and strlen($k) == strspn(strtolower($k), "abcdefghijklmnopqrstuvwxyz0123456789"))
        $result .= html::em('arg', array(
          'name' => $k,
          ), $v);

    return html::wrap('getArgs', $result);
  }

  private static function isSection(Context $ctx, $param)
  {
    $sth = $ctx->db->prepare("SELECT 1 FROM node WHERE id = ? AND class = 'tag'");
    $sth->execute(array($param));
    return $sth->fetchColumn(0);
  }

  private static function getNameFromQuery($query)
  {
    if (empty($query))
      return 'index';

    foreach ($parts = explode('/', $query) as $k => $v)
      if (is_numeric($v))
        unset($parts[$k]);

    return implode('-', $parts);
  }

  private static function findStyleSheet($themeName, $pageName)
  {
    if (!is_dir($prefix = os::path(MCMS_SITE_FOLDER, 'themes', $themeName)))
      $prefix = os::path(MCMS_SITE_FOLDER, 'themes', 'default');

    foreach (array($pageName, 'default') as $name) {
      $path = os::path($prefix, 'templates', 'page.' . $name . '.xsl');
      if (file_exists($path))
        return $path;
    }
  }

  /**
   * Обновление списка страниц.
   * @mcms_message ru.molinos.cms.reload
   */
  public static function on_install(Context $ctx)
  {
    $fileName = MCMS_ROOT . DIRECTORY_SEPARATOR . MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'route.php';
    if (file_exists($fileName))
      unlink($fileName);

    if (false === self::load()) {
      $map = array();
      self::rebuild($ctx, $map, null, 'GET');
      self::save($map);
    }
  }

  private static function rebuild(Context $ctx, array &$map, $parent = null, $prefix = null)
  {
    $nodes = Node::find($ctx->db, array(
      'class' => 'domain',
      'deleted' => 0,
      'published' => 1,
      'parent_id' => $parent,
      ));

    foreach ($nodes as $node) {
      $key = $prefix . '/' . $node->name;
      $handler = array();

      foreach (array('title', 'language', 'content_type', 'theme', 'defaultsection') as $k)
        if (isset($node->$k))
          $handler[$k] = $node->$k;

      $widgets = array();
      foreach ($node->getLinked('widget') as $w)
        $widgets[] = $w->name;
      if (!empty($widgets))
        $handler['widgets'] = implode(',', $widgets);

      $handler['call'] = 'BaseRoute::serve';
      if (!isset($handler['cache']))
        $handler['cache'] = 60;

      switch ($node->params) {
      case 'doc':
      case 'sec':
        $handler['optional'] = true;
        $map[$key . '/'.'*'] = $handler;
        break;
      case 'sec+doc':
        $handler['optional'] = true;
        $map[$key . '/'.'*'] = $handler;
        $map[$key . '/'.'*'.'/'.'*'] = $handler;
        break;
      default:
        $map[$key] = $handler;
      }

      self::rebuild($ctx, $map, $node->id, $key);
    }
  }

  public static function load()
  {
    if (!is_readable($fileName = self::getRouteFileName()))
      return false;
    return ini::read($fileName);
  }

  public static function save(array $map)
  {
    $comment = "; Таблица маршрутизации Molinos CMS.\n"
      . "; Обновлена " . mcms::now() . ".\n"
      . "; http://code.google.com/p/molinos-cms/wiki/DevGuideRouters\n";

    ini::write(self::getRouteFileName(), $map, $comment);

    Router::flush();
  }

  private static function getRouteFileName()
  {
    return MCMS_ROOT . DIRECTORY_SEPARATOR . MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'route.ini';
  }
}
