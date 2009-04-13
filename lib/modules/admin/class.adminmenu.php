<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminMenu
{
  private $items;

  public function __construct($items = array())
  {
    $this->items = $items;
  }

  public function poll(Context $ctx)
  {
    $cachekey = 'admin/menu/raw/' . $ctx->user->id;

    if (!is_array($this->items = mcms::cache($cachekey)) or empty($this->items)) {
      $data = $ctx->registry->poll('ru.molinos.cms.admin.menu', array($ctx));

      $this->items = $dynamic = array();

      foreach ($data as $class) {
        foreach ($class['result'] as $method) {
          if (empty($method['method'])) {
            $call = null;
            $method['submenu'] = true;
          } elseif (false === strpos($method['method'], '::')) {
            $call = array($class['class'], $method['method']);
          } else {
            $call = $method['method'];
          }

          if (is_callable($call) or !empty($method['submenu'])) {
            if (!empty($method['re'])) {
              $verb = isset($method['verb']) ? $method['verb'] : 'get';
              $key = strtoupper($verb) . '/' . $method['re'];

              if ($re = (false !== strpos($key, '*')))
                $key = str_replace('\*', '([^/]+)', preg_quote($key, '|'));
              unset($method['re']);

              $method['method'] = $call;
              $method['level'] = count(explode('/', $key));

              if ($re)
                $dynamic[$key] = $method;
              else
                $this->items[$key] = $method;
            }
          }
        }
      }

      ksort($this->items);
      if (!empty($dynamic)) {
        ksort($dynamic);
        $this->items['#re'] = $dynamic;
      }

      mcms::cache($cachekey, $this->items);
    }

    return !empty($this->items);
  }

  public function dispatch(Context $ctx)
  {
    $args = array();
    $query = strtoupper($ctx->method()) . '/' . $ctx->query();

    if (array_key_exists($query, $this->items)) {
      $match = $this->items[$query];
    } elseif (!empty($this->items['#re'])) {
      foreach ($this->items['#re'] as $k => $v) {
        if (preg_match('|^' . $k . '$|', $query, $m)) {
          array_shift($m);
          $args = $m;
          $match = $v;
          break;
        }
      }
    }

    if (isset($match)) {
      // Подменю всегда рендерим самостоятельно.
      if (!empty($match['submenu'])) {
        $args[] = $query;
        $match['method'] = array($this, 'renderSubmenu');
      }

      // Если параметров нет — передаём запрос.
      if (empty($args))
        $args[] = $query;

      array_unshift($args, $ctx);

      $output = call_user_func_array($match['method'], $args);
      if (empty($output))
        throw new RuntimeException(t('Обработчик этого адреса — %method — ничего не вернул.', array(
          '%method' => $handler['method'] . '()',
          )));
      return $output;
    }

    return false;
  }

  public function getSubMenu($path)
  {
    $items = array();
    $level = $this->items[$path]['level'] + 1;

    $items[$path] = $this->items[$path];

    foreach ($this->items as $k => $v) {
      if (0 === strpos($k, $path . '/') and $v['level'] >= $level)
        $items[$k] = $v;
    }

    return new AdminMenu($items);
  }

  public function renderSubMenu(Context $ctx, $path)
  {
    $sub = $this->getSubMenu($path)->getXML('content', array('type' => 'submenu'));
    // mcms::debug($path, $sub);
    return $sub;
  }

  public function getXML($em = 'menu', array $options = array())
  {
    $tmp = '';
    $prefix = empty($_GET['__cleanurls']) ? '?q=' : '';

    list($top) = array_values($this->items);

    foreach ($this->items as $k => $v) {
      if (empty($v['title']))
        continue;
      if ($v['level'] != $top['level'] + 1)
        continue;
      if (0 !== strpos($k, 'GET/'))
        continue;

      $inside = '';
      foreach ($this->items as $k1 => $v1) {
        if (!empty($v1['title']) and $v1['level'] == $v['level'] + 1) {
          if (0 === strpos($k1, $k . '/')) {
            $v1['re'] = null;
            $v1['level'] = null;
            $v1['name'] = $prefix . substr($k1, 4);
            $inside .= html::em('path', $v1);
          }
        }
      }

      $v['re'] = null;
      $v['level'] = null;
      $v['name'] = $prefix . substr($k, 4);
      $tmp .= html::em('path', $v, $inside);
    }

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
  public function getPath($query)
  {
    $path = array();

    $tabs = explode('/', $query);
    $tab = empty($tabs[1])
      ? null
      : 'admin/' . $tabs[1];

    while ($query) {
      if (!empty($this->items[$query])) {
        $em = $this->items[$query];
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
};
