<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Router
{
  const ini = false;

  private $static;
  private $dynamic;

  public function __construct($items = array())
  {
    $this->static = $items;
  }

  public function poll(Context $ctx)
  {
    list($this->static, $this->dynamic) = $this->load($ctx);
    return $this;
  }

  /**
   * Вытаскивает таблицу маршрутизации из кэша, если не находит — читает из .php файла.
   */
  private function load(Context $ctx, $reload = false)
  {
    $ckey = 'route';
    $cache = cache::getInstance();

    if (!is_array($result = $cache->$ckey) or $reload) {
      $result = $this->load_php($ctx);
      $cache->$ckey = $result;
    }

    return $result;
  }

  /**
   * Обновление кэша маршрутов.
   */
  public static function reload()
  {
    $tmp = new Router();
    $tmp->load(Context::last(), true);
  }

  /**
   * Вытаскивает таблицу маршрутизации из .php файла, если не находит — читает из .ini.
   */
  private function load_php(Context $ctx)
  {
    $static = $dynamic = array();
    $raw = $this->load_ini($ctx);

    // Проставляем уровни вложенности.
    foreach ($raw as $k => $v)
      $raw[$k]['level'] = count(explode('/', $k));

    foreach ($raw as $key => $handler) {
      if ($re = (false !== strpos($key, '*'))) {
        $key = str_replace('\*', '([^/]+)', preg_quote($key, '|'));
        if (')' == substr($key, -1) and !empty($handler['optional']))
          $key .= '?';
        $dynamic[$key] = $handler;
      } else {
        $static[$key] = $handler;
      }
    }

    ksort($static);
    ksort($dynamic);

    $result = array($static, $dynamic);

    return $result;
  }

  /**
   * Вытаскивает таблицу маршрутизации из .ini файла, если не находит — возвращает пустой массив.
   */
  private function load_ini(Context $ctx)
  {
    $raw = array();

    foreach ($ctx->config->get('routes', array()) as $k => $v)
      $raw['GET/' . $k] = $v;

    foreach (os::find('lib', 'modules', '*', 'route.ini') as $iniFile) {
      foreach (ini::read($iniFile) as $k => $v) {
        if (is_array($v)) {
          $raw[$k] = $v;
          $raw[$k]['static'] = true;
        } elseif ('dynamic' == $k and false !== strpos($v, '::')) {
          list($class, $method) = explode('::', $v);
          if (method_exists($class, $method)) {
            try {
              if (is_array($tmp = call_user_func(array($class, $method), $ctx))) {
                foreach ($tmp as $k => $v)
                  $raw[$k] = $v;
              }
            } catch (Exception $e) {
              Logger::log(get_class($e) . ' in ' . $v . '()');
            }
          }
        }
      }
    }

    // Опрос динамических маршрутов.
    foreach ((array)$ctx->registry->poll('ru.molinos.cms.route.poll') as $tmp)
      if (!empty($tmp['result']))
        foreach ($tmp['result'] as $k => $v)
          $raw[$k] = $v;

    return $raw;
  }

  public function dispatch(Context $ctx)
  {
    $cache = cache::getInstance();
    $ckey = $this->getCacheKey();

    // Если страница есть в кэше — выводим сразу, даже не ищем маршрут.
    if (empty($_GET['nocache']) and is_array($cached = $cache->$ckey)) {
      if (isset($cached['expires']) and time() < $cached['expires']) {
        Logger::log('hit: ' . MCMS_HOST_NAME . $_SERVER['REQUEST_URI'], 'cache');
        header('HTTP/1.1 ' . $cached['code'] . ' ' . $cached['text']);
        header('Content-Type: ' . $cached['type']);
        header('Content-Length: ' . $cached['length']);
        die($cached['content']);
      }
    }

    if (false === ($tmp = $this->find($ctx, $ctx->query())))
      $tmp = $this->find($ctx, 'errors/404');

    if (!empty($tmp[0]['cache']))
      Logger::log('mis: ' . MCMS_HOST_NAME . '/' . $ctx->query(), 'cache');
    elseif (0 !== strpos($ctx->query(), 'api/')) {
      Logger::log('ign: ' . MCMS_HOST_NAME . '/' . $ctx->query(), 'cache');
    }

    if ($ctx->debug('route'))
      mcms::debug($tmp, $this);

    if (false === $tmp)
      return false;

    list($match, $args) = $tmp;

    if (!empty($match['call'])) {
      array_unshift($args, $match);
      array_unshift($args, $ctx->query());
      array_unshift($args, $ctx);

      list($class, $method) = explode('::', $match['call']);
      if (!class_exists($class) or !method_exists($class, $method))
        throw new RuntimeException(t('Неверный обработчик: <tt>%call()</tt>.', array(
          '%call' => $match['call'],
          )));

      $output = call_user_func_array($match['call'], $args);
      if (empty($output))
        throw new RuntimeException(t('Обработчик этого адреса — %call — ничего не вернул.', array(
          '%call' => $match['call'] . '()',
          )));

      if ($output instanceof Response and !empty($match['cache']))
        $output->setCache($cache, $ckey, $match['cache']);

      return $output;
    }

    return false;
  }

  private function getCacheKey()
  {
    return 'page:' . str_replace('www.', '', MCMS_HOST_NAME) . MCMS_REQUEST_URI;
  }

  public function find(Context $ctx, $realQuery)
  {
    $args = array();
    $query = '/' . $realQuery;
    $prefix = strtoupper($ctx->method()) . '/';

    $q = array(
      $prefix . $query,
      $prefix . $ctx->host() . $query,
      $prefix . 'localhost' . $query,
      );

    foreach ($q as $mask) {
      if (array_key_exists($mask, $this->static))
        return array($this->static[$mask], array());
      if (false !== ($tmp = $this->findre($mask)))
        return $tmp;
    }

    return false;
  }

  private function findre($query)
  {
    foreach ($this->dynamic as $re => $handler) {
      if (preg_match('|^' . $re . '$|', $query, $m)) {
        array_shift($m);
        return array($handler, $m);
      }
    }

    return false;
  }

  /**
   * Возвращает статические маршруты для формирования меню.
   */
  public function getStatic()
  {
    return $this->static;
  }
}
