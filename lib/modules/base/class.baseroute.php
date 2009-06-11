<?php

class BaseRoute
{
  public static function serve(Context $ctx, $query, array $handler, $param = null)
  {
    // TODO: делать это в xslt::transform(), если необходимо.
    APIStream::init($ctx);

    if (empty($param) and !empty($handler['default']))
      $param = $handler['default'];

    $content = '';
    $page = array(
      'status' => 200,
      'name' => empty($handler['pagename'])
        ? self::getNameFromQuery($query, $param)
        : $handler['pagename'],
      'title' => null,
      'host' => MCMS_HOST_NAME,
      'peer' => $_SERVER['REMOTE_ADDR'],
      'back' => urlencode($_SERVER['REQUEST_URI']),
      'back_raw' => $_SERVER['REQUEST_URI'],
      'prefix' => null,
      'base' => $ctx->url()->getBase($ctx),
      'version' => MCMS_VERSION,
      // TODO: добавить поддержку ?xslt=client
      'api' => 'cms://localhost/api/',
      'uid' => $ctx->user->id,
      'query' => $ctx->query(),
      'param' => $param,
      );

    if (isset($handler['title']))
      $page['title'] = $handler['title'];

    $theme = empty($handler['theme'])
      ? 'default'
      : $handler['theme'];

    if (null !== $ctx->get('theme') and $ctx->canDebug())
      $theme = $ctx->get('theme');

    $page['prefix'] = MCMS_SITE_FOLDER . '/themes/' . $theme;

    $content = $ctx->url()->getArgsXML();

    try {
      foreach ((array)$ctx->registry->poll('ru.molinos.cms.page.content', array($ctx, $handler, $param)) as $block)
        if (!empty($block['result']))
          $content .= $block['result'];
    } catch (UserErrorException $e) {
      Logger::trace($e);
      $page['status'] = $e->getCode();
      $page['error'] = get_class($e);
      $page['title'] = $e->getMessage();
    } catch (Exception $e) {
      Logger::trace($e);
      $page['status'] = 500;
      $page['error'] = get_class($e);
      $page['title'] = $e->getMessage();
    }

    try {
      foreach ((array)$ctx->registry->poll('ru.molinos.cms.page.head', array($ctx, $handler, $param)) as $block)
        if (!empty($block['result']))
          $content .= $block['result'];
    } catch (Exception $e) {
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

  private static function getNameFromQuery($query, $param)
  {
    if (empty($query))
      return 'index';

    $parts = explode('/', $query);

    if (!empty($param))
      $parts = array_diff($parts, array($param));

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
   * FIXME: удалить, не нужно это больше.
   */
  public static function on_install(Context $ctx)
  {
    $fileName = MCMS_ROOT . DIRECTORY_SEPARATOR . MCMS_SITE_FOLDER . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'route.php';
    if (file_exists($fileName))
      unlink($fileName);

    if (false === self::load($ctx)) {
      $map = array();
      self::rebuild($ctx, $map, null, 'GET');
      self::save($map);
    }
  }

  private static function rebuild(Context $ctx, array &$map, $parent = null, $prefix = null)
  {
    $nodes = Node::find(array(
      'class' => 'domain',
      'deleted' => 0,
      'published' => 1,
      'parent_id' => $parent,
      ), $ctx->db);

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

      mcms::debug($handler);

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

  public static function load(Context $ctx)
  {
    return $ctx->config->get('routes', false);
  }

  public static function save(array $map)
  {
    $comment = "; Таблица маршрутизации Molinos CMS.\n"
      . "; Обновлена " . mcms::now() . ".\n"
      . "; http://code.google.com/p/molinos-cms/wiki/DevGuideRouters\n";

    $config = Context::last()->config;
    $config['routes'] = $map;
    $config->save();

    Router::reload();
  }

  /**
   * Выдаёт в страницу запрошенный объект, проверяет доступ.
   * @mcms_message ru.molinos.cms.page.content
   */
  public static function on_get_current_node(Context $ctx, $handler, $param)
  {
    if (!empty($param)) {
      $data = $ctx->db->fetch("SELECT `published`, `deleted`, `xml` FROM `node` WHERE `id` = ?", array($param));

      if (empty($data))
        throw new PageNotFoundException();
      elseif (empty($data['xml']))
        throw new ForbiddenException(t('Объект не предназначен для отображения.'));
      elseif (!empty($data['deleted']))
        throw new PageNotFoundException(t('Объект удалён.'));
      elseif (empty($data['published']))
        throw new ForbiddenException();

      return $data['xml'];
    }
  }
}
