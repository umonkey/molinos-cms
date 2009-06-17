<?php

class AdminMenu
{
  private $items;
  private static $permcache = array();

  public function __construct(array $items)
  {
    $this->items = $items;
  }

  public function getSubMenu(Context $ctx)
  {
    $path = 'GET//' . $ctx->query();

    $items = array();
    $level = $this->items[$path]['level'] + 1;

    $items[$path] = $this->items[$path];

    foreach ($this->items as $k => $v) {
      if (0 === strpos($k, $path . '/') and $v['level'] >= $level) {
        if ($this->checkPerm($ctx, $v)) {
          $items[$k] = $v;
        }
      }
    }

    if (1 == count($items))
      return false;

    return new AdminMenu($items);
  }

  public function getXML(Context $ctx, $em = 'menu', array $options = array())
  {
    $tmp = '';
    $prefix = empty($_GET['__cleanurls']) ? '?q=' : '';

    list($top) = array_values($this->items);

    foreach ($this->items as $k => $v) {
      if (empty($v['title']))
        continue;
      if ($v['level'] != $top['level'] + 1)
        continue;
      if (0 !== strpos($k, 'GET//'))
        continue;
      if (!$this->checkPerm($ctx, $v))
        continue;

      $inside = '';
      foreach ($this->items as $k1 => $v1) {
        if (!empty($v1['title']) and $v1['level'] == $v['level'] + 1) {
          if (0 === strpos($k1, $k . '/') and $this->checkPerm($ctx, $v1)) {
            $v1['re'] = null;
            $v1['level'] = null;
            $v1['name'] = $prefix . substr($k1, 4);
            $v1['call'] = null;
            $v1['next'] = null;
            $inside .= html::em('path', $v1);
          }
        }
      }

      if (empty($inside)) {
        if (isset($v['call']) and 'AdminUI::submenu' == $v['call'])
          continue;
        if (isset($v['next']) and 'AdminUI::submenu' == $v['next'])
          continue;
      }

      $v['call'] = null;
      $v['next'] = null;
      $v['re'] = null;
      $v['level'] = null;
      $v['name'] = $prefix . substr($k, 4);
      $tmp .= html::em('path', $v, $inside);
    }

    if (empty($tmp))
      return false;

    $top['call'] = null;
    $top['next'] = null;
    $top['re'] = null;
    $top['level'] = null;
    $top['path'] = null;
    $top['method'] = null;
    $top['submenu'] = null;

    return html::em($em, array_merge($top, $options), $tmp);
  }

  /**
   * Возвращает путь к указанной странице.
   */
  public function getPath(Context $ctx)
  {
    $path = array();
    $query = $ctx->query();

    $tabs = explode('/', $query);
    $tab = empty($tabs[1])
      ? null
      : 'admin/' . $tabs[1];

    while ($query) {
      if (!empty($this->static [$query])) {
        $em = $this->static[$query];
        $em['name'] = $query;
        array_unshift($path, $em);
      }

      $query = substr($query, 0, strrpos($query, '/'));
    }

    $output = '';
    foreach ($path as $em)
      $output .= self::path($em);

    return html::em('location', array(
      'tab' => $tab,
      ), $output);
  }

  private static function path(array $options, $path = null, $inside = null)
  {
    $options['re'] = null;
    $options['method'] = null;
    $options['level'] = null;
    if (null !== $path)
      $options['name'] = $path;
    return html::em('path', $options, $inside);
  }

  private function checkPerm(Context $ctx, array $item)
  {
    if (!array_key_exists('perms', $item))
      return true;

    if (!array_key_exists($item['perms'], self::$permcache)) {
      if ('debug' == $item['perms'])
        $result = $ctx->canDebug();
      else {
        list($mode, $type) = explode(',', $item['perms']);
        $result = $ctx->user->hasAccess($mode, $type);
      }
      self::$permcache[$item['perms']] = $result;
    }

    return self::$permcache[$item['perms']];
  }

  /**
   * Добавление маршрутов.
   * @route GET//api/admin/menu.xml
   */
  public static function on_get_menu(Context $ctx)
  {
    $router = new Router();
    $router->poll($ctx);
    $menu = new AdminMenu($router->getStatic());

    return new Response($menu->getXML($ctx), 'text/xml');
  }
}
