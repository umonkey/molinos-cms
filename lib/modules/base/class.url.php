<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:
//
// TODO: надо научиться корректно рекомбинировать чистые урлы при установке CMS в папку,
// например: http://bebop.pdg.ru/path/to/cms/admin/ — надо вырезать /path/to/cms/ из пути
// при парсинге.  Извне урлы приходят в нормальном виде, т.к. срабатывает mod_rewrite и
// в параметр q= он передаёт только admin/, а вот при ручном парсинге возникают проблемы.

class url
{
  private static $clean = null;
  private static $localhost = null;
  private static $root = null;

  private $scheme = null;
  private $host = null;
  private $path = null;
  private $args = null;
  private $anchor = null;
  private $islocal = null;

  // Парсинг ссылки, вход: массив или строка.
  public function __construct($source = null)
  {
    self::init();

    if (is_array($source))
      $this->fromArray($source);
    elseif (is_string($source))
      $this->fromString($source);
    elseif (!empty($_SERVER['QUERY_STRING']))
      $this->fromString('?'. $_SERVER['QUERY_STRING']);
    else
      $this->fromArray(array());

    if ($this->islocal and $this->path != trim($this->path, '/'))
      $this->path = trim($this->path, '/');
  }

  public function __toString()
  {
    $isfile = false;

    if ('mailto' == $this->scheme)
      return 'mailto:'. urlencode($this->path);

    if ($this->islocal) {
      // Удаляем index.php — незачем его указывать.
      if ('index.php' == ($path = trim($this->path, '/')))
        $path = '';

      // Ссылки на существующие файлы оставляем неизменными.
      elseif (file_exists(MCMS_ROOT .'/'. $path))
        $isfile = true;

      // Остаются внутренние ссылки к несуществующим файлам, они зависят
      // от поддержки «чистых урлов».  Если поддержки нет — просто удаляем
      // путь, в ?q= его преобразует метод getArgsAsString().
      elseif (!self::$clean)
        $path = '';

      elseif ('.rpc' != substr($path, -4))
        $path .= '/';

      $result = sprintf('%s://%s/%s', $this->scheme, $this->host,
        ltrim(self::$root . $path . $this->getArgsAsString($isfile), '/'));
    } else {
      $result = sprintf('%s://%s/%s%s', $this->scheme, $this->host, $this->path, $this->getArgsAsString());
    }

    return $result;
  }

  public function as_array()
  {
    return array(
      'scheme' => $this->scheme,
      'host' => $this->host,
      'path' => $this->path,
      'args' => $this->args,
      'anchor' => $this->anchor,
      );
  }

  public function __get($key)
  {
    switch ($key) {
    case 'scheme':
    case 'host':
    case 'path':
    case 'args':
    case 'anchor':
    case 'islocal':
      return $this->$key;
    default:
      throw new InvalidArgumentException(t('Свойство '. $key .' у ссылки отсутствует.'));
    }
  }

  public static function path()
  {
    if (null === self::$root)
      self::init();
    return self::$root;
  }

  private static function init()
  {
    if (null === self::$clean) {
      self::$clean = mcms::config('cleanurls');
      self::$localhost = empty($_SERVER['HTTP_HOST']) ? 'example.com' : $_SERVER['HTTP_HOST'];

      if (empty($_SERVER['REMOTE_ADDR']))
        self::$root = '';
      else
        self::$root = trim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'])), '/') .'/';
    }
  }

  private function fromString($source)
  {
    $url = parse_url($source);

    // Парсим дополнительные параметры.
    if (array_key_exists('query', $url)) {
      $url['args'] = $this->parse_request_args($url['query']);
      unset($url['query']);
    }

    // Дальше работаем как с массивом.
    $this->fromArray($url);
  }

  private function parse_request_args($string)
  {
    $res = $keys = array();

    foreach (explode('&', $string) as $element) {
      $parts = explode('=', $element, 2);

      $k = $parts[0];
      if (count($parts) > 1)
        $v = $parts[1];
      else
        $v = '';

      // Упрощаем жизнь парсеру, удаляя пустые ключи.
      if ($v == '')
        continue;

      // Заворачиваем начальные конструкции: "group.key"
      $k = preg_replace('/^([a-z0-9_]+)\.([a-z0-9_]+)/i', '\1%5B\2%5D', $k);

      // Заменяем все остальные точки на ][, т.к. они будут находиться внутри массива.
      // $k = str_replace('.', '%5D%5B', $k);

      $keys[] = $k .'='. $v;
    }

    parse_str(join('&', $keys), $res);
    return $res;
  }

  private function fromArray(array $url)
  {
    // Применяем некоторые дефолты.
    $url = $this->complement($url);

    // Для локальных ссылок предпочитаем q реальному пути.
    if ($this->islocal = (self::$localhost == $url['host'])) {
      if (array_key_exists('q', $url['args'])) {
        $url['path'] = trim($url['args']['q'], '/');
        unset($url['args']['q']);
      }

      // Если CMS установлена в папку (/cms/), при парсинге урлов надо удалять
      // эту папку из пути, чтобы /cms/index.php превращалось в /index.php, итд.
      //
      // Без этого возникают проблемы если, например, мы пытаемся вернуться по
      // адресу, указанному в $_GET['destination'] — %2Fcms%2Findex.php.  Если
      // путь к CMS не вырезать, он продублируется (получится /cms/cms/index.php).
      //
      // TODO: поправить парсер так, чтобы он корректно обрабатывал относительные
      // и абсолютные урлы (не пытался придавлять self::path() к тому, что начинается
      // со слэша), но тогда возникнет другая проблема: в коде надо будет очень
      // внимательно формировать ссылки: l(/compress.rpc) будет работать на большинстве
      // инсталляций — с поддержкой mod_rewrite и с установкой в корень, но в остальных
      // ситуациях будут возникать неожиданные проблемы.

      if (substr($url['path'], 1, strlen(self::path())) == self::path())
        $url['path'] = substr($url['path'], strlen(self::path()));
    }

    foreach (array('scheme', 'host', 'path', 'args', 'anchor') as $key)
      $this->$key = $url[$key];
  }

  private function complement(array $url)
  {
    return array_merge(array(
      'scheme' => empty($_SERVER['HTTPS']) ? 'http' : 'https',
      'host' => self::$localhost,
      'path' => '',
      'args' => array(),
      'anchor' => '',
      ), $url);
  }

  private function getArgsAsString($isfile = false)
  {
    $result = '';

    $args = $this->args;

    if ($this->islocal and !$isfile) {
      if (self::$clean or 'index.php' == $this->path)
        $args['q'] = null;
      else
        $args['q'] = trim($this->path, '/');
    }

    ksort($args);

    if (!empty($args)) {
      $forbidden = array('nocache', 'widget', '__cleanurls');

      $pairs = array();

      foreach ($args as $k => $v) {
        if ($v === null)
          continue;

        elseif (is_array($v)) {
          ksort($v);

          foreach ($v as $argname => $argval) {
            $prefix = $k .'.'. $argname;

            if (is_array($argval)) {
              foreach ($argval as $k1 => $v1) {
                if (is_numeric($k1))
                  $pairs[] = $prefix .'[]='. urlencode($v1);
                elseif (is_array($v1))
                  ;
                else
                  $pairs[] = "{$prefix}[{$k1}]=". urlencode($v1);
              }
            }

            elseif (null !== $argval and '' !== $argval) {
              $pairs[] = $prefix .'='. urlencode($argval);
            }
          }
        }

        elseif ($v !== '' and !in_array($k, $forbidden)) {
          if ('destination' === $k and 'CURRENT' === $v) {
            $pairs[] = $k .'='. urlencode($_SERVER['REQUEST_URI']);
          } else {
            $pairs[] = $k .'='. urlencode($v);
          }
        }
      }

      if (!empty($pairs))
        $result = '?'. join('&', $pairs);
    }

    return $result;
  }

  // test hacks

  public function __setclean($value)
  {
    self::$clean = empty($value) ? false : true;
  }
}
