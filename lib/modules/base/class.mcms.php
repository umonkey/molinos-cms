<?php
/**
 * This is the main utility class for Molinos CMS.
 *
 * This class contains frequently used functions and shortcuts
 * to functions provider by different modules.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * The main utility class for Molinos CMS
 *
 * @package mod_base
 * @subpackage Core
 */
class mcms
{
  const MEDIA_AUDIO = 1;
  const MEDIA_VIDEO = 2;
  const MEDIA_IMAGE = 4;

  const FLUSH_NOW = 1;

  const VERSION_CURRENT = 1;
  const VERSION_RELEASE = 2;
  const VERSION_AVAILABLE = 3;
  const VERSION_AVAILABLE_URL = 4;

  private static $extras = array();

  /**
   * Renders an HTML element.
   *
   * Returns the HTML representation of an element described by
   * input parameters which are: element name, an array of attributes,
   * and the content.  Except for the first parameter, all is optional.
   *
   * @return string
   * @author Justin Forest
   */
  public static function html()
  {
    if (func_num_args() == 0 or func_num_args() > 3)
      throw new InvalidArgumentException(t('mcms::html() принимает от одного до трёх параметров.'));
    else {
      $args = func_get_args();
      $name = array_shift($args);

      if (empty($name))
        throw new InvalidArgumentException(t('Попытка создать HTML элемент без имени.'));

      $parts = null;
      $content = null;

      if (is_array($tmp = array_shift($args)))
        $parts = $tmp;
      else
        $content = $tmp;

      if (!empty($args))
        $content = array_shift($args);
    }

    $output = '<'. $name;

    if (('td' == $name or 'th' == $name) and empty($content))
      $content = '&nbsp;';

    if (empty($parts))
      $parts = array();

    $fixmap = array(
      'a' => 'href',
      'form' => 'action',
      );

    // Замена CURRENT на текущий адрес.
    if (array_key_exists($name, $fixmap)) {
      if (array_key_exists($fixmap[$name], $parts)) {
        $parts[$fixmap[$name]] = str_replace(array(
          '&destination=CURRENT',
          '?destination=CURRENT',
          ), array(
          '&destination='. urlencode($_SERVER['REQUEST_URI']),
          '?destination='. urlencode($_SERVER['REQUEST_URI']),
          ), strval($parts[$fixmap[$name]]));
      }
    }

    $output .= self::htmlattrs($parts);

    if (null === $content and !in_array($name, array('a', 'script', 'div', 'textarea', 'span'))) {
      $output .= ' />';
    } else {
      $output .= '>'. $content .'</'. $name .'>';
    }

    return $output;
  }

  public static function htmlattrs(array $attrs)
  {
    $result = '';

    foreach ($attrs as $k => $v) {
      if (!empty($v)) {
        if (is_array($v))
          if ($k == 'class')
            $v = join(' ', $v);
          else {
            $v = null;
          }

        $result .= ' '.$k.'=\''. mcms_plain($v, false) .'\'';
      } elseif ($k == 'value') {
        $result .= " value=''";
      }
    }

    return $result;
  }

  public static function parse_html($text)
  {
    $attrs = array();

    if (preg_match_all('@\s+([a-z]+)=("([^"]*)"|\'([^\']*)\')@', $text, $m)) {
      foreach ($m[1] as $idx => $key) {
        $attrs[$key] = $m[3][$idx];
      }
    }

    return $attrs;
  }

  public static function mediaGetPlayer(array $files, $types = null, array $custom_options = array())
  {
    $nodes = array();
    $firstfile = null;
    $havetypes = 0;

    if (null === $types)
      $types = self::MEDIA_AUDIO | self::MEDIA_VIDEO;

    foreach ($files as $k => $v) {
      $flink = null;

      if ($v instanceof Node) {
        $v = $v->getRaw();
      } elseif (is_string($v)) {
        $flink = $v;
        $v = array(
          'id' => null,
          'filepath' => $v,
          'filetype' => bebop_get_file_type($v),
          );
      }

      if (null === $flink)
        $flink = "attachment/{$v['id']}/{$v['filename']}";

      switch ($v['filetype']) {
      case 'audio/mpeg':
      case 'audio/x-mpegurl':
        if ($types & self::MEDIA_AUDIO) {
          $nodes[] = $v['id'];
          $havetypes |= self::MEDIA_AUDIO;
          if (null === $firstfile)
            $firstfile = $flink;
        }
        break;
      case 'video/flv':
      case 'video/x-flv':
        if ($types & self::MEDIA_VIDEO) {
          $nodes[] = $v['id'];
          $havetypes |= self::MEDIA_VIDEO;
          if (null === $firstfile)
            $firstfile = $flink;
        }
        break;
      }
    }

    // Подходящих файлов нет, выходим.
    if (empty($nodes))
      return null;

    // Всего один файл — используем напрямую.
    if (count($nodes) == 1 or !mcms::ismodule('playlist'))
      $playlist = $firstfile;
    else
      $playlist = l('?q=playlist.rpc&nodes='. join(',', $nodes));

    // Нет генератора плэйлистов, выходим.

    // Параметризация проигрывателя.
    $options = array_merge(array(
      'file' => $playlist,
      'showdigits' => 'true',
      'autostart' => 'false',
      'repeat' => 'false',
      'shuffle' => 'false',
      'width' => 350,
      'height' => 100,
      'showdownload' => 'false',
      'displayheight' => 0,
      ), $custom_options);

    if ($havetypes & self::MEDIA_VIDEO) {
      $dheight = ($options['width'] / 4) * 3;
      $options['displayheight'] = $dheight;

      if (count($nodes) < 2)
        $options['height'] = 0;

      $options['height'] += $dheight;
    } elseif (count($nodes) == 1) {
      $options['height'] = 20;
    }

    $args = array();

    foreach ($options as $k => $v)
      $args[] = $k .'='. urlencode($v);

    $url = 'themes/all/flash/player.swf?'. join('&', $args);

    $params = mcms::html('param', array(
      'name' => 'movie',
      'value' => $url,
      ));
    $params .= mcms::html('param', array(
      'name' => 'wmode',
      'value' => 'transparent',
      ));

    return mcms::html('object', array(
      'type' => 'application/x-shockwave-flash',
      'data' => $url,
      'width' => $options['width'],
      'height' => $options['height'],
      ), $params);
  }

  public static function cache()
  {
    $result = null;

    if (null === ($cache = BebopCache::getInstance()))
      return $result;

    $args = func_get_args();

    if (count($args))
      $key = mcms::config('filename') .':'. $args[0];

    switch (count($args)) {
    case 1:
      $result = $cache->$key;
      break;
    case 2:
      $cache->$key = $args[1];
      break;
    }

    return $result;
  }

  public static function pcache()
  {
    if (!count($args = func_get_args()))
      throw new InvalidArgumentException(t('Количество параметров для mcms::pcace() должно быть 1 или 2.'));

    $cache = BebopCache::getInstance();

    $key = '.pcache.'. $args[0];
    $fname = mcms::config('tmpdir') .'/'. $key
      .md5(MCMS_ROOT .','.  $_SERVER['HTTP_HOST']);

    switch (count($args)) {
    case 1:
      if (false !== ($tmp = $cache->$key))
        return $tmp;

      elseif (file_exists($fname) and false !== ($tmp = unserialize(file_get_contents($fname))))
        return $tmp;

      return false;

    case 2:
      if (null === $args[1]) {
        if (file_exists($fname))
          unlink($fname);
        unset($cache->$key);
      } else {
        if (is_writable(dirname($fname))) {
          if (file_exists($fname))
            unlink($fname);
          file_put_contents($fname, serialize($args[1]));
        }
        $cache->$key = $args[1];
      }

      return $args[1];
    }
  }

  public static function config($key, $default = null)
  {
    if (!class_exists('Config'))
      self::fatal('Отсутствует поддержка конфигурационных файлов.');

    return isset(Config::getInstance()->$key)
      ? Config::getInstance()->$key
      : $default;
  }

  public static function modconf($modulename, $key = null, $default = null)
  {
    static $cache = array();

    if (!array_key_exists($modulename, $cache)) {
      $data = array();
      $ckey = 'moduleinfo:'. $modulename;

      if (is_array($tmp = mcms::cache($ckey)))
        $data = $tmp;
      else {
        try {
          $node = Node::load(array('class' => 'moduleinfo', 'name' => $modulename), true);

          if (is_array($tmp = $node->config)) {
            mcms::cache($ckey, $data = $tmp);
          }
        } catch (Exception $e) {
          if (!($e instanceof ObjectNotFoundException) and !($e instanceof PDOException))
            throw $e;
        }
      }

      $cache[$modulename] = $tmp;
    }

    if (null !== $key)
      return empty($cache[$modulename][$key]) ? $default : $cache[$modulename][$key];
    else
      return $cache[$modulename];
  }

  public static function ismodule($name)
  {
    static $enabled = null;

    // FIXME: придумать решение по-лучше
    switch ($name) {
    case 'admin':
    case 'attachment':
    case 'auth':
    case 'base':
    case 'cache':
    case 'cron':
    case 'exchange':
    case 'imgtoolkit':
    case 'install':
    case 'mimemail':
    case 'nodeapi':
    case 'openid':
    case 'pdo':
    case 'update':
      return true;
    }

    if (null === $enabled)
      $enabled = mcms::config('runtime.modules');

    return in_array($name, $enabled);
  }

  public static function flush($flags = null)
  {
    if (null !== ($cache = BebopCache::getInstance()))
      $cache->flush($flags & self::FLUSH_NOW ? true : false);
  }

  public static function db($name = 'default')
  {
    if (null === ($ctx = Context::last()))
      throw new RuntimeException(t('Обращение к БД вне контекста.'));
    return $ctx->db;
  }

  public static function user()
  {
    if (func_num_args())
      throw new InvalidArgumentException(t('mcms::user() не принимает '
        .'параметров и возвращает объект, описывающий текущего '
        .'пользователя, анонимного или идентифицированного.  Для '
        .'идентификации используйте User::authorize().'));
    return User::identify();
  }

  public static function invoke($interface, $method, array $args = array())
  {
    $res = array();

    foreach (self::getImplementors($interface) as $class)
      if (class_exists($class))
        $res[] = call_user_func_array(array($class, $method), $args);

    return $res;
  }

  public static function invoke_module($module, $interface, $method, array &$args = array())
  {
    $res = false;

    foreach (self::getImplementors($interface, $module) as $class) {
      if (class_exists($class))
        $res = call_user_func_array(array($class, $method), $args);
    }

    return $res;
  }

  /**
   * Получение списка методов, обрабатывающих вызов.
   */
  public static function getImplementors($interface, $module = null)
  {
    $list = array();

    if (array_key_exists($if = strtolower($interface), $map = Loader::map('interfaces'))) {
      $rev = Loader::map('rclasses');

      foreach ($map[$if] as $class) {
        // Указан конкретный модуль, и текущий класс находится не в нём.
        if ($module !== null and $rev[$class] != $module)
          continue;

        // Класс находится в отключенном модуле.
        if (!mcms::ismodule($rev[$class]))
          continue;

        $list[] = $class;
      }
    }

    return $list;
  }

  public static function redirect($path, $status = 301)
  {
    if (!in_array($status, array('301', '302', '303', '307')))
      throw new Exception("Статус перенаправления {$status} не определён в стандарте HTTP/1.1");

    mcms::flush(mcms::FLUSH_NOW);

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
      $status = 303;

    if ($ctx = Context::last()) {
      if (isset($ctx->db)) {
        try {
          $ctx->db->commit();
        } catch (NotConnectedException $e) { }
      }
    }

    $url = new url($path);
    $target = mcms::fixurl($url->getAbsolute());

    mcms::log('redirect', $target);

    // При работе с JSON возвращаем адрес.
    bebop_on_json(array(
      'status' => 'redirect',
      'redirect' => $target,
      ));

    if (!headers_sent()) {
      header('HTTP/1.1 '. $status .' Redirect');
      header('Location: '. $target);
    } else {
      die("now please go to {$target}\n"
        ."Somebody screw up the cron by PRINTING something.\n");
    }

    exit();
  }

  // Отладочные функции.
  public static function debug()
  {
    if (($ctx = Context::last()) and $ctx->canDebug()) {
      if (ob_get_length())
        ob_end_clean();

      $output = array();

      if (func_num_args()) {
        foreach (func_get_args() as $arg) {
          if (is_resource($arg))
            $output[] = 'resource';
          elseif (is_string($arg) and !empty($arg))
            $output[] = $arg;
          else
            $output[] = preg_replace('/ =>\s+/', ' => ', var_export($arg, true))
              .';';
        }
      } else {
        $output[] = 'breakpoint';
      }

      bebop_on_json(array('args' => $output));

      if (ob_get_length())
        ob_end_clean();

      if (!empty($_SERVER['REQUEST_METHOD']))
        header("Content-Type: text/plain; charset=utf-8");

      print join("\n\n", $output) ."\n\n";

      if (true /* !empty($_SERVER['REMOTE_ADDR']) */) {
        printf("--- backtrace (request duration: %s) ---\n",
          microtime(true) - MCMS_START_TIME);
        print mcms::backtrace();

        if (function_exists('memory_get_peak_usage'))
          printf("\n--- memory usage ---\nnow:  %s\npeak: %s\n",
            self::filesize(memory_get_usage()),
            self::filesize(memory_get_peak_usage()));

        try {
          if (isset($ctx->db) and null !== ($log = $ctx->db->getLog())) {
            $idx = 1;
            printf("\n--- SQL log ---\n");

            foreach ($log as $sql) {
              if (substr($sql, 0, 2) == '--')
                printf("     %s\n", $sql);
              else {
                if (false !== ($pos = strpos($sql, ', -- timing: ')))
                  $sql = substr($sql, 0, $pos) ."\n       ". substr($sql, $pos + 5);
                printf("%3d. %s\n", $idx++, $sql);
              }
            }
          }
        } catch (Exception $e) { }
      }

      die();
    }
  }

  public static function log($op, $message, $nid = null)
  {
    if (mcms::ismodule('syslog'))
      SysLogModule::log($op, $message, $nid);
    else
      self::flog($op, $message);
  }

  public static function flog($op, $message)
  {
    if ($message instanceof Exception)
      $message = get_class($message) . ': ' . $message->getMessage();
    error_log("[{$op}] {$message}", 0);
  }

  public static function url($text, $url)
  {
    if (!is_array($url))
      $url = bebop_split_url($url);
    return mcms::html('a', array(
      'href' => bebop_combine_url($url, false),
      ), $text);
  }

  public static function report(Exception $e)
  {
    if (null === ($recipient = mcms::config('backtracerecipient')))
      return;

    switch (get_class($e)) {
    case 'ObjectNotFoundException':
    case 'UnauthorizedException':
    case 'ForbiddenException':
    case 'PageNotFoundException':
      return;
    }

    $body = t('<p>%method request for %url from %ip resulted in an %class exception (code %code) with the following message:</p>', array(
      '%method' => $_SERVER['REQUEST_METHOD'],
      '%url' => 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
      '%ip' => $_SERVER['REMOTE_ADDR'],
      '%class' => get_class($e),
      '%code' => $e->getCode(),
      ));

    $body .= '<blockquote><em>'. mcms_plain($e->getMessage()) .'</em></blockquote>';

    $body .= t('<p>Here is the stack trace:</p><pre>%stack</pre>', array(
      '%stack' => mcms::backtrace($e),
      ));

    if (mcms::user()->id)
      $body .= t('<p>The user was identified as %user (#%uid).</p>', array(
        '%user' => l(mcms::user()->getRaw()),
        '%uid' => mcms::user()->id,
        ));
    else
      $body .= t('<p>The user responsible for this action could not be identified.</p>');

    $body .= t('<p>The server runs Molinos.CMS version %version (<a href="%buglist">see the bug list</a>).</p>', array(
      '%version' => mcms::version(),
      '%buglist' => preg_replace('/^(\d+\.\d+)\..*$/', 'http://code.google.com/p/molinos-cms/issues/list?q=label:Milestone-R\1', mcms::version()),
      ));

    $subject = 'Molinos.CMS crash report for '. $_SERVER['HTTP_HOST'];

    $rc = BebopMimeMail::send('cms-bugs@molinos.ru', $recipient, $subject, $body);
  }

  public static function captchaGen()
  {
    if ((mcms::user()->id != 0) or !mcms::ismodule('captcha'))
      return null;

    $result = strtolower(substr(base64_encode(rand()), 0, 6));
    return $result;
  }

  public static function captchaCheck(array $data)
  {
    if (mcms::user()->id != 0)
      return true;

    if (!mcms::ismodule('captcha'))
      return true;

    if (!empty($data['captcha']) and is_array($data['captcha']) and count($data['captcha']) == 2) {
      $usr = $data['captcha'][0];
      $ref = mcms_decrypt($data['captcha'][1]);

      if (0 === strcmp($usr, $ref))
        return true;
    }

    throw new ForbiddenException(t('Проверьте правильность ввода текста с изображения.'));
  }

  // Возвращает список доступных классов и файлов, в которых они описаны.
  // Информация о классах кэшируется в tmp/.classes.php или -- если доступен
  // класс BebopCache -- в более быстром кэше.
  public static function getClassMap()
  {
    $tmp = self::getModuleMap();
    return $tmp['classes'];
  }

  public static function getModuleMap($name = null)
  {
    return Loader::map();
  }

  public static function enableModules(array $list)
  {
    $tmp = Config::getInstance();
    $tmp->set('runtime.modules', $list);
    $tmp->write();

    Loader::rebuild();
  }

  // Проверяет, существует ли указанный класс.  В отличие от базовой
  // версии class_exists() не использует автозагрузку, но проверяет
  // список классов, доступных для неё.
  public static function class_exists($name)
  {
    if (class_exists($name, false))
      return true;
    if (array_key_exists(strtolower($name), self::getClassMap()))
      return true;
    return false;
  }

  public static function pager($total, $current, $limit,
    $paramname = 'page', $default = 1)
  {
    $result = array();

    if (empty($limit))
      return null;

    $result['documents'] = $total;
    $result['pages'] = $pages = ceil($total / $limit);
    $result['perpage'] = intval($limit);
    $result['current'] = $current;

    if ('last' == $current)
      $result['current'] = $current = $pages;

    if ('last' == $default)
      $default = $pages;

    if ($pages > 0) {
      // Немного валидации.
      if ($current > $pages or $current <= 0)
        throw new UserErrorException("Страница не найдена", 404, "Страница не найдена", "Вы обратились к странице {$current} списка, содержащего {$pages} страниц.&nbsp; Это недопустимо.");

      // С какой страницы начинаем список?
      $beg = max(1, $current - 5);
      // На какой заканчиваем?
      $end = min($pages, $current + 5);

      // Расщеплённый текущий урл.
      $url = new url();

      for ($i = $beg; $i <= $end; $i++) {
        $url->setarg($paramname, ($i == $default) ? '' : $i);
        $result['list'][$i] = ($i == $current) ? '' : $url->string();
      }

      if (!empty($result['list'][$current - 1]))
        $result['prev'] = $result['list'][$current - 1];
      if (!empty($result['list'][$current + 1]))
        $result['next'] = $result['list'][$current + 1];
    }

    return $result;
  }

  public static function fatal()
  {
    $message = 'Unknown fatal error.';
    $extras = array();
    $backtrace = mcms::backtrace();

    $args = func_get_args();

    if (count($args)) {
      if ($args[0] instanceof Exception) {
        $message = $args[0]->getMessage();
        $backtrace = mcms::backtrace($args[0]);
        array_shift($args);
      } elseif (is_string($args[0])) {
        $message = $args[0];
        array_shift($args);
      }
    }

    if (!(($ctx = Context::last()) and $ctx->canDebug()))
      $backtrace = null;

    bebop_on_json(array(
      'status' => 'error',
      'message' => $message,
      ));

    foreach ($args as $arg)
      $extras[] = var_export($arg, true) .';';

    if (ob_get_length())
      ob_end_clean();

    if (!empty($_SERVER['REQUEST_METHOD'])) {
      header('HTTP/1.1 500 Internal Server Error');
      header("Content-Type: text/html; charset=utf-8");

      $html = '<html><head><title>Fatal Error</title></head><body>'
        .'<h1>Fatal Error</h1><p>'. $message .'</p>';

      if (null !== $backtrace)
        $html .= '<h2>Стэк вызова</h2><pre>'. $backtrace .'</pre>';

      $html .= '<hr/>'. self::getSignature();
      $html .= '</body></html>';

      $content = mcms::fixurls($html, false);

      $report = sprintf("--- Request method: %s ---\n--- Host: %s ---\n--- URL: %s ---\n\n%s", $_SERVER['REQUEST_METHOD'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], $content);
      self::writeCrashDump($report);

      header('Content-Length: '. strlen($content));
      echo $content;

      die();
    }
    
    $backtrace = sprintf("--- backtrace (time: %s) ---\n\n%s", microtime(), $backtrace);
    self::writeCrashDump($backtrace);
    echo $backtrace;

    die();
  }

  private static function writeCrashDump($contents)
  {
    // Узнаем, куда же складывать дампы.
    // Если такой директории нет, пытаемся создать.
    $dumpdir = mcms::config('dumpdir', 'tmp/crashdump');
    if (!is_dir($dumpdir))
      mkdir($dumpdir);
    // Задаем файл для выгрузки дампа и проверяем на наличие,
    // если существует - добавляем случайный мусор в название.
    $dumpfile = $dumpdir . '/' . date('Y-m-d-') . md5(serialize($_SERVER));
    if (file_exists($dumpfile))
      $dumpfile .= rand();
    $dumpfile .= '.log';

    if (is_writable($dumpdir))
      file_put_contents($dumpfile, $contents);
  }

  public static function mail($from = null, $to, $subject, $text)
  {
    foreach ((array)$to as $re)
      return MsgModule::send($from, $re, $subject, $text);
  }

  public static function version($mode = mcms::VERSION_CURRENT)
  {
    switch ($mode) {
    case self::VERSION_CURRENT:
      return MCMS_VERSION;

    case self::VERSION_RELEASE:
      return substr(MCMS_VERSION, 0, - strlen(strrchr(MCMS_VERSION, '.')));

    case self::VERSION_AVAILABLE:
      $release = self::version(self::VERSION_RELEASE);
      $content = mcms_fetch_file('http://code.google.com/p/molinos-cms/downloads/list?q=label:R'. $release);

      if (preg_match($re = "@http://molinos-cms\.googlecode\.com/files/molinos-cms-({$release}\.[0-9]+)\.zip@", $content, $m))
        return $m[1];
      else
        return MCMS_VERSION;

    case self::VERSION_AVAILABLE_URL:
      return 'http://molinos-cms.googlecode.com/files/molinos-cms-'. self::version(self::VERSION_AVAILABLE) .'.zip';
    }

    return MCMS_VERSION;
  }

  public static function backtrace($stack = null)
  {
    $output = '';

    if ($stack instanceof Exception) {
      $tmp = $stack->getTrace();
      array_unshift($tmp, array(
        'file' => $stack->getFile(),
        'line' => $stack->getLine(),
        'function' => sprintf('throw new %s', get_class($stack)),
        ));
      $stack = $tmp;
    } elseif (null === $stack or !is_array($stack)) {
      $stack = debug_backtrace();
      array_shift($stack);
    }

    $libdir = 'lib'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR;

    foreach ($stack as $k => $v) {
      if (!empty($v['file']))
        $v['file'] = preg_replace('@.*'. preg_quote($libdir) .'@', $libdir, $v['file']);

      if (!empty($v['class']))
        $func = $v['class'] .$v['type']. $v['function'];
      else
        $func = $v['function'];

      $output .= sprintf("%2d. ", $k + 1);

      if (!empty($v['file']) and !empty($v['line']))
        $output .= sprintf('%s(%d) — ', ltrim(str_replace(MCMS_ROOT, '', $v['file']), '/'), $v['line']);
      else
        $output .= '??? — ';

      $output .= $func .'()';

      $output .= "\n";
    }

    return $output;
  }

  public static function error_handler($errno, $errstr, $errfile, $errline, array $context)
  {
    if ($errno == 2048)
      return;

    if ($errno == 2 and substr($errstr, 0, 5) == 'dl():')
      return;

    if (ob_get_length())
      ob_end_clean();

    if (($ctx = Context::last()) and $ctx->canDebug()) {
      $output = "\nError {$errno}: {$errstr}.\n";
      $output .= sprintf("File: %s at line %d.\n", ltrim(str_replace(MCMS_ROOT, '', $errfile), '/'), $errline);
      $output .= "\nDon't panic.  You see this message because you're listed as a debugger.\n";
      $output .= "Regular web site visitors don't see this message.\n";

      if ($errno & 4437)
        $output .= "They most likely see a White Screen Of Death.\n\n";
      elseif (ini_get('error_reporting'))
        $output .= "They most likely see some damaged HTML markup.\n\n";
      else
        $output .= "\n";

      $output .= "--- backtrace ---\n";
      $output .= mcms::backtrace();

      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: '. strlen($output));
      die($output);
    }
  }

  public static function shutdown_handler()
  {
    try {
      if (($ctx = Context::last()) and isset($ctx->db))
        $ctx->db->rollback();
    } catch (Exception $e) { }

    if (null !== ($e = error_get_last()) and ($e['type'] & (E_ERROR|E_RECOVERABLE_ERROR))) {
      if (null !== ($re = mcms::config('backtracerecipient'))) {
        $release = substr(mcms::version(), 0, -(strrpos(mcms::version(), '.') + 1));

        $message = mcms::render(__CLASS__, array(
          'mode' => 'fatal',
          'url' => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
          'host' => $_SERVER['HTTP_HOST'],
          'code' => $e['type'],
          'line' => $e['line'],
          'file' => ltrim(str_replace(MCMS_ROOT, '', $e['file']), '/'),
          'text' => $e['message'],
          'version' => mcms::version(),
          ));

        $subject = t('Фатальная ошибка на %host', array('%host' => $_SERVER['HTTP_HOST']));

        BebopMimeMail::send(null, $re, $subject, $message);

        if (ob_get_length())
          ob_end_clean();

        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');

        print "Случилось что-то страшное.  Администрация сервера поставлена в известность, скоро всё должно снова заработать.\n";

        if (($ctx = Context::last()) and $ctx->canDebug())
          printf("\n--- 8< --- Отладочная информация --- 8< ---\n\n"
            ."Код ошибки: %s\n"
            ."   Локация: %s(%s)\n"
            ." Сообщение: %s\n"
            ."    Версия: %s\n",
            $e['type'], ltrim(str_replace(MCMS_ROOT, '', $e['file']), '/'), $e['line'], $e['message'], mcms::version());

        die();
      }
    }
  }

  public static function mkdir($path, $msg = null)
  {
    // Быстрая проверка на случай существования, чтобы не парсить лишний раз.
    if (!is_dir($path)) {
      $parts = explode('/', $path);
      $path = substr($path, 0, 1) == '/' ? '' : MCMS_ROOT;

      while (!empty($parts)) {
        $dir = array_shift($parts);
        $next = $path .'/'. $dir;

        if (!is_dir($next)) {
          if (!is_writable($path)) {
            if (null === $msg)
              $msg = 'Каталог %path отсутствует и не может быть создан.';
            throw new RuntimeException(t($msg, array('%path' => $next)));
          } else {
            mkdir($next, 0770);
          }
        }

        $path = $next;
      }
    }

    return realpath($path);
  }

  public static function now()
  {
    return date('Y-m-d H:i:s', time() - date('Z', time()));
  }

  // Изспользуется в шаблонах для добавления стилей и скриптов.
  public static function extras($filename = null, $compress = true)
  {
    if (null !== $filename)
      if (!array_key_exists($filename, self::$extras))
        self::$extras[$filename] = $compress;

    $result = self::$extras;

    if (null === $filename) {
      self::$extras = array();
      return self::format_extras($result);
    }

    return $result;
  }

  public static function get_extras()
  {
    $t = self::$extras;
    self::$extras = array();
    return $t;
  }

  public static function set_extras(array $extras)
  {
    return self::$extras = $extras;
  }

  public static function add_extras(array $extras)
  {
    foreach ($extras as $k => $v)
      self::$extras[$k] = $v;
  }

  // FIXME: оптимизировать!
  private static function pop(array &$a, $e)
  {
    $repack = array();

    foreach ($a as $k => $v)
      if ($k == $e)
        $repack[$k] = $v;

    foreach ($a as $k => $v)
      if ($k != $e)
        $repack[$k] = $v;

    $a = $repack;
  }

  private static function format_extras(array $extras)
  {
    $root = mcms::path();

    $output = mcms::html('script', array(
      'type' => 'text/javascript',
      ), 'var mcms_path = \''. $root .'\';') ."\n";

    foreach ($extras as $k => $v) {
      if (0 === strpos($k, 'script:')) {
        $output .= mcms::html('script', array(
          'type' => 'text/javascript',
          ), substr($k, 7));
      }
    }

    // Проталкиваем jQuery на первое место.
    // FIXME: нужно более вменяемое решение.
    self::pop($extras, 'lib/modules/tinymce/editor/tiny_mce_gzip.js');
    self::pop($extras, 'lib/modules/tinymce/editor/tiny_mce_src.js');
    self::pop($extras, 'lib/modules/tinymce/editor/tiny_mce.js');
    self::pop($extras, 'themes/all/jquery/jquery.min.js');
    self::pop($extras, 'themes/all/jquery/jquery.js');

    $compress = (class_exists('CompressorModule') and empty($_GET['nocompress']));

    $js = $css = '';

    // Заход первый: выводим некомпрессируемые объекты
    // или все объекты, если нет компрессора.
    foreach ($extras as $file => $ok) {
      if (!$ok or !$compress) {
        if ('.js' == substr($file, -3))
          $js .= mcms::html('script', array(
            'type' => 'text/javascript',
            'src' => $file,
            )) ."\n";
        elseif ('.css' == substr($file, -4))
          $css .= mcms::html('link', array(
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => $file,
            )) ."\n";
      }
    }

    $output .= $css . $js;

    // Заход второй: компрессируем всё, что можно.
    if ($compress)
      $output .= $xyz = CompressorModule::format($extras);

    return $output;
  }

  public static function deprecated($break = false)
  {
    $frame = array_slice(debug_backtrace(), 1, 1);
    $frame = array_pop($frame);

    $func = $frame['function'] .'()';
    $line = ltrim(str_replace(MCMS_ROOT, '', $frame['file']), '/')
      .'('. $frame['line'] .')';

    mcms::log('debug', $msg = 'deprecated function '
      .$func .' called from '. $line);

    if ($break)
      mcms::debug($msg);
  }

  public static function path()
  {
    static $path = null;

    if (null === $path) {
      if (!empty($_SERVER['HTTP_HOST'])) { //скрипт запускается из под web-сервера
        $path = empty($_GET['__rootpath'])
          ? dirname($_SERVER['SCRIPT_NAME'])
          : $_GET['__rootpath'];

        if ('/' == ($path = '/'. trim($path, '/')))
          $path = '';
      }
      else { //скрипт запускается из командной строки
        $curpath = $_SERVER['PWD'];
        $p = strpos($curpath,'/lib/');

        if ($p > 0) {
          $curpath = substr($curpath, 0, $p);
          $path    = '/'.basename($curpath);

          //хак - найдём родительский каталог для $path. Если это sites - то склеим его
          $prefixdir = dirname($curpath);
          $pdir2 =  basename($prefixdir);
          if ($pdir2 == 'sites')
            $path =  '/'.$pdir2.$path;
        }
      }
    }

    return $path;
  }

  public static function session()
  {
    $args = func_get_args();

    $s = Session::instance();

    if (empty($args))
      return $s;
    elseif (count($args) == 1)
      return $s->$args[0];
    elseif (count($args) >= 2)
      $s->$args[0] = $args[1];
  }

  public static function render($filename, array $data)
  {
    if (!file_exists($filename) and class_exists($filename, false)) {
      $key = strtolower($filename);
      $map = self::getClassMap();

      if (array_key_exists($key, $map))
        $filename = str_replace('.php', '.phtml', $map[$key]);
    }

    if (!is_readable($filename))
      return false;

    if ('/' == substr($filename, 0, 1))
      $__fullpath = $filename;
    else
      $__fullpath = MCMS_ROOT .'/'. $filename;

    if (file_exists($__fullpath)) {
      $data['prefix'] = rtrim(dirname(dirname($filename)), '/');

      ob_start();

      $ext = strrchr($filename, '.');

      if ($ext == '.tpl') {
        if (class_exists('BebopSmarty')) {
          $with_debug = (false !== strstr($filename, 'page.'));
          $__smarty = new BebopSmarty($with_debug);
          $__smarty->template_dir = ($__dir = dirname($__fullpath));

          if (is_dir($__dir .'/plugins')) {
            $__plugins = $__smarty->plugins_dir;
            $__plugins[] = $__dir .'/plugins';
            $__smarty->plugins_dir = $__plugins;
          }

          foreach ($data as $k => $v)
            $__smarty->assign($k, $v);

          error_reporting(($old = error_reporting()) & ~E_NOTICE);

          $compile_id = md5($__fullpath);
          $__smarty->display($__fullpath, $compile_id, $compile_id);

          error_reporting($old);
        }
      }

      elseif ($ext == '.php' or $ext == '.phtml') {
        extract($data, EXTR_SKIP);
        include($__fullpath);
      }

      $output = ob_get_clean();

      if (file_exists($tmp = str_replace($ext, '.css', $filename)))
        mcms::extras($tmp);

      if (file_exists($tmp = str_replace($ext, '.js', $filename)))
        mcms::extras($tmp);

      return trim($output);
    }
  }

  /**
   * Проверка окружения.
   *
   * Если отсутствуют жизненно важные расширения PHP или настройки несовместимы
   * с жизнью — выводит сообщение об ошибке, в противном случае ничего не
   * делает. Вызывать следует один раз, в начале обработки запроса (см.
   * index.php).
   */
  public static function check()
  {
    $htreq = array(
      'register_globals' => 0,
      'magic_quotes_gpc' => 0,
      'magic_quotes_runtime' => 0,
      'magic_quotes_sybase' => 0,
      '@upload_tmp_dir' => mcms::mkdir(mcms::config('tmpdir') .'/upload'),
      );

    $errors = $messages = array();

    foreach ($htreq as $k => $v) {
      $key = substr($k, 0, 1) == '@' ? substr($k, 1) : $k;

      ini_set($key, $v);

      if (($v != ($current = ini_get($key))) and (substr($k, 0, 1) != '@'))
        $errors[] = $key;
    }

    if (!extension_loaded('pdo'))
      $messages[] = t('Отсутствует поддержка <a href=\'@url\'>PDO</a>.  Она очень нужна, '
        .'без неё не получится работать с базами данных.', array(
          '@url' => 'http://docs.php.net/pdo',
          ));

    if (!extension_loaded('mbstring'))
      $messages[] = t('Отсутствует поддержка юникода.  21й век на дворе, '
        .'пожалуйста, установите расширение '
        .'<a href=\'http://php.net/mbstring\'>mbstring</a>.');
    elseif (!mb_internal_encoding('UTF-8'))
      $messages[] = t('Не удалось установить UTF-8 в качестве '
        .'базовой кодировки для модуля mbstr.');

    mcms::mkdir(mcms::config('filestorage'), 'Каталог для загружаемых '
      .'пользователями файлов (<tt>%path</tt>) закрыт для записи. '
      .'Очень важно, чтобы в него можно было писать.');

    if (!empty($errors) or !empty($messages)) {
      $output = "<html><head><title>Ошибка конфигурации</title></head><body>";

      if (!empty($errors)) {
        $output .= '<h1>'. t('Нарушение безопасности') .'</h1>';
        $output .= "<p>Следующие настройки <a href='http://php.net/'>PHP</a> неверны и не могут быть <a href='http://php.net/ini_set'>изменены на лету</a>:</p>";
        $output .= "<table border='1'><tr><th>Параметр</th><th>Значение</th><th>Требуется</th></tr>";

        foreach ($errors as $key)
          $output .= "<tr><td>{$key}</td><td>". ini_get($key) ."</td><td>{$htreq[$key]}</td></tr>";

        $output .= "</table>";
      }

      if (!empty($messages)) {
        $output .= '<h1>'. t('Ошибка настройки') .'</h1>';
        $output .= '<ol><li>'. join('</li><li>', $messages) .'</li></ol>';
      }

      $output .= '<p>'. t('Свяжитесь с администратором вашего хостинга для исправления этих проблем.&nbsp; <a href=\'http://code.google.com/p/molinos-cms/\'>Molinos.CMS</a> на данный момент не может работать.') .'</p>';
      $output .= "</body></html>";

      header('HTTP/1.1 500 Security Error');
      header('Content-Type: text/html; charset=utf-8');
      header('Content-Length: '. strlen($output));
      die($output);
    }
  }

  public static function getSignature(Context $ctx = null, $full = false)
  {
    if (null === $ctx)
      $ctx = new Context();

    $at = mcms::html('a', array(
      'href' => $ctx->url()->getBase($ctx),
      ), $ctx->host() . $ctx->folder());

    $link = 'http://code.google.com/p/molinos-cms/wiki/ChangeLog_' . str_replace('.', '_', mcms::version());
    $sig = '<em>Molinos CMS ' . l($link, 'v' . mcms::version());

    if ($full) {
      $options = array();

      if (count($parts = explode(':', mcms::config('db.default'))))
        if (in_array($parts[0], PDO_Singleton::listDrivers()))
          $options[] = $parts[0];

      $options[] = str_replace('_provider', '', get_class(BebopCache::getInstance()));
      $options[] = ini_get('memory_limit');

      $sig .= ' [' . join('+', $options) . ']';
    }

    $sig .= ' at '. $at .'</em>';

    return $sig;
  }

  public static function run(Context $ctx = null)
  {
    // Проверка готовности окружения.
    self::check();

    if (null === $ctx)
      $ctx = new Context(array('url' => '?' . $_SERVER['QUERY_STRING']));

    $ctx->db = mcms::config('db.default');

    if ($ctx->get('flush') and $ctx->canDebug()) {
      mcms::flush();
      mcms::flush(mcms::FLUSH_NOW);
    }

    try {
      if (false === ($result = Page::render($ctx, $ctx->host(), $ctx->query())))
        throw new PageNotFoundException();
    }

    catch (NotConnectedException $e) {
      if ('install.rpc' == $ctx->query())
        mcms::fatal($e);
      $ctx->redirect('?q=install.rpc&action=db&destination=CURRENT');
    }
    
    catch (NotInstalledException $e) {
      if ('install.rpc' == $ctx->query())
        mcms::fatal($e);
      $ctx->redirect('?q=install.rpc&action=' . $e->get_type()
        . '&destination=CURRENT');
    }
    
    catch (UserErrorException $e) {
      if ($ctx->debug('errors'))
        mcms::fatal($e);

      try {
        $result = Page::render($ctx, $ctx->host(), 'errors/' . $e->getCode());
      } catch (Exception $e2) {
        mcms::fatal(new Exception(t('<p>Ошибка %code: %message.</p><p>Более «красивый» обработчик этой ошибки можно сделать, добавив страницу «errors/%code».</p>', array(
          '%code' => $e->getCode(),
          '%message' => rtrim($e->getMessage(), '.'),
          ))));
      }

      if (false === $result) {
        $e = new PageNotFoundException(t('Запрошенная вами страница не найдена. Кроме того, обработчик ошибки (errors/%code) также не найден.', array(
          '%code' => $e->getCode(),
          )));
        mcms::fatal($e);
      }
    }

    catch (Exception $e) {
      mcms::fatal($e);
    }

    // Информация о ходе выполнения запроса.
    $result['content'] = str_replace(
      array(
        '$request_time',
        '$peak_memory',
        ),
      array(
        microtime(true) - MCMS_START_TIME,
        self::filesize(memory_get_peak_usage()),
        ),
      $result['content']);

    $ctx->profile();

    /*
    if ($ctx->debug('profile')) {
      $message = "Profiling.\n\n";

      $message .= sprintf("%-30s %-20s %7s\n", 'name', 'time', 'queries');

      foreach (mcms::profile('get') as $k => $v)
        $message .= sprintf("%-30s %-20s %7s\n", $k, $v['time'], $v['queries']);

      mcms::debug($message);
    }
    */

    if (!empty($result['headers']))
      foreach ($result['headers'] as $h)
        header($h);

    die(self::fixurls($result['content'], false));

    /*
    try {
      $req = new RequestController($ctx);
      $output = $req->run();
    } catch (UserErrorException $e) {
      if (mcms::config('debug.errors'))
        mcms::fatal($e);

      if ('errors' == $ctx->debug())
        mcms::fatal($e);

      if ($e->getCode()) {
        // Ошибка 404 — пытаемся использовать подстановку.
        if (404 == $e->getCode()) {
          try {
            $new = $ctx->db->getResults("SELECT * FROM `node__fallback` "
              ."WHERE old = ?", array($ctx->query()));

            if (!empty($new[0]['new']))
              $ctx->redirect($new[0]['new'], 302);

            if (empty($new))
              $ctx->db->exec("INSERT INTO `node__fallback` "
                ."(`old`, `new`, `ref`) VALUES (?, ?, ?)",
                array($ctx->query(), null, $_SERVER['HTTP_REFERER']));
          } catch (Exception $e2) { }
        }

        // Пытаемся вывести страницу /$статус
        try {
          $req = new RequestController($c2 = new Context(array(
            'url' => $ctx->url()->getBase($ctx) . 'errors/'. $e->getCode(),
            )));
          $output = $req->run();
        } catch (PageNotFoundException $e2) {
          $output = null;
        }

        if (empty($output)) {
          $url = new url($ctx->url()->getBase($ctx)
              .'?q=admin&mode=tree&preset=pages&cgroup=structure');

          $extra = ($e->getCode() == 401)
            ? t('<p>Вы можете авторизоваться <a href=\'@url\'>здесь</a>.</p>',
              array('@url' => '?q=admin&destination=CURRENT'))
            : null;

          mcms::fatal(t('<p>При обработке запроса возникла ошибка '
            .'%code:<br/><strong>%name</strong>.</p>%extra'
            .'<p><em>PS: этот обработчик ошибок можно '
            .'заменить на произвольный, <a href="@url">создав '
            .'страницу</a> «errors/%code» в корне сайта.</em></p>',
            array(
              '%code' => $e->getCode(),
              '%name' => trim($e->getMessage(), '.'),
              '%extra' => $extra,
              '@url' => $url->string(),
              )));
        }

        header(sprintf('HTTP/1.1 %s %s', $e->getCode(),
          self::getHttpStatusMessage($e->getCode())));
      } else {
        throw $e;
      }
    }

    mcms::fixurls($output, true);
    */
  }

  public static function getHttpStatusMessage($code)
  {
    $map = array(
      404 => 'Not Found',
      );

    if (!array_key_exists($code, $map))
      return 'Unknown Error';
    else
      return $map[$code];
  }

  public static function matchip($value, $ips)
  {
    if (is_array($ips))
      $ips = join(',', $ips);

    $re = preg_replace(
      array('@,\s*@', '@\.@', '@\*@', '@\?@'),
      array('|', '\.', '.*', '.'),
      '@^('. $ips .')$@');

    return preg_match($re, $value)
      ? true
      : false;
  }

  public static function filesize($file_or_size)
  {
    if (!is_numeric($file_or_size)) {
      if (!file_exists($file_or_size))
        $file_or_size = 0;
      else
        $file_or_size = filesize($file_or_size);
    }

    $sfx = 'Б';

    if ($file_or_size > 1048576) {
      $file_or_size /= 1048576;
      $sfx = 'МБ';
    } elseif ($file_or_size > 1024) {
      $file_or_size /= 1024;
      $sfx = 'КБ';
    }

    return str_replace('.0', '', number_format($file_or_size, 1, '.', ''))
      . $sfx;
  }

  public static function fixurls($content, $send = false)
  {
    // Замена "грязных" ссылок на "чистые".
    if (!empty($_GET['__cleanurls']) and strlen($content) < 500000) {
      $re = '@(?:href|src|action)=(?:"([^"]+)"|\'([^\']+)\')@';

      if (preg_match_all($re, $content, $m)) {
        foreach ($m[0] as $idx => $source) {
          $link = empty($m[1][$idx])
            ? $m[2][$idx]
            : $m[1][$idx];

          if (0 === strpos($link, '?q=')) {
            $parts = explode('&amp;', substr($link, 3), 2);
            $new = str_replace($link, join('?', $parts), $source);
            $content = str_replace($source, $new, $content);
          }
        }
      }
    }

    if ($send) {
      header('Content-Length: '. strlen($content));
      die($content);
    }

    return $content;
  }

  public static function fixurl($url)
  {
    if (!empty($_GET['__cleanurls']) and false !== strpos($url, '?q=')) {
      $parts = explode('?q=', $url, 2);
      $url = join(array($parts[0], join('?', explode('&', $parts[1], 2))));
    }

    return $url;
  }

  public static function embed($url, $options = array(), $nothing = null)
  {
    $link = array();
    $options = array_merge(array('width' => 425, 'height' => 318), $options);

    if (null === $nothing)
      $nothing = ''; // 'Для просмотра этого ролика нужен Flash.';

    if (strtolower(substr($url, -4)) == '.mp3') {
      $furl = urlencode($url);
      $link['type'] = 'audio/mpeg';
      $link['is_audio'] = true;
      $link['embed'] = "<object type='application/x-shockwave-flash' data='themes/all/flash/player.swf?file={$furl}&amp;showdigits=true&amp;autostart=false&amp;repeat=false&amp;shuffle=false&amp;width=350&amp;height=20&amp;showdownload=false&amp;displayheight=0' width='350' height='20'><param name='movie' value='themes/all/flash/player.swf?file={$furl}&amp;showdigits=true&amp;autostart=false&amp;repeat=false&amp;shuffle=false&amp;width=350&amp;height=20&amp;showdownload=false&amp;displayheight=0' /><param name='wmode' value='transparent' /></object>";
    } elseif (preg_match('%^http://vimeo.com/([0-9]+)%', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object width="'. $options['width'] .'" height="'. $options['height'] .'">'
        .'<param name="allowfullscreen" value="true" />'
        .'<param name="allowscriptaccess" value="always" />'
        .'<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id='. $m1[1] .'&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=00ADEF&fullscreen=1" />'
        .'<embed src="http://vimeo.com/moogaloop.swf?clip_id='. $m1[1] .'&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=00ADEF&fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="'. $options['width'] .'" height="'. $options['height'] .'">'
        .'</embed></object>';
      $link['is_video'] = true;
      $link['host'] = 'Vimeo';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://video\.google\.com/videoplay\?docid=([0-9\-]+)%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object width="'.$options['width'].'" height="'.$options['height'].'"><param name="movie" value="http://video.google.com/googleplayer.swf?docId='.$m1[1].'&amp;hl=en"></param><param name="wmode" value="transparent"></param><embed src="http://video.google.com/googleplayer.swf?docId='.$m1[1].'&amp;hl=en" type="application/x-shockwave-flash" wmode="transparent" width="'.$options['width'].'" height="'.$options['height'].'"></embed></object>';
      $link['is_video'] = true;
      $link['host'] = 'Google Video';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://([a-z0-9]+\.){0,1}youtube\.com/(?:watch\?v=|v/)([^&]+)%i', $url, $m1)) {
      $o = mcms::html('param', array(
        'name' => 'movie',
        'value' => 'http://www.youtube.com/v/'. $m1[2],
        ));
      $o .= mcms::html('param', array(
        'name' => 'wmode',
        'value' => 'transparent',
        ));
      $o .= mcms::html('embed', array(
        'src' => 'http://www.youtube.com/v/'. $m1[2],
        'type' => 'application/x-shockwave-flash',
        'wmode' => 'transparent',
        'width' => $options['width'],
        'height' => $options['height'],
        ), $nothing);
      $link['embed'] = mcms::html('object', array(
        'width' => $options['width'],
        'height' => $options['height'],
        ), $o);
      $link['type'] = 'video/x-flv';
      $link['is_video'] = true;
      $link['host'] = 'YouTube';
      $link['vid'] = $m1[2];
      $link['thumbnail'] = 'http://img.youtube.com/vi/' . $m1[2] . '/2.jpg';
    } elseif (preg_match('%^http://vids\.myspace\.com/index.cfm\?fuseaction=[^&]+\&(?:amp;){0,1}videoID=([0-9]+)%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<embed src="http://lads.myspace.com/videos/vplayer.swf" flashvars="m='.$m1[1].'&type=video" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'"></embed>';
      $link['is_video'] = true;
      $link['host'] = 'MySpace';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://vision\.rambler\.ru/users/(.+)$%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="'.$options['width'].'" height="'.$options['height'].'"><param name="wmode" value="transparent"></param><param name="movie" value="http://vision.rambler.ru/i/e.swf?id='.$m1[1].'&logo=1" /><embed src="http://vision.rambler.ru/i/e.swf?id='.$m1[1].'&logo=1" width="'.$options['width'].'" height="'.$options['height'].'" type="application/x-shockwave-flash" wmode="transparent"/></object>';
      $link['is_video'] = true;
      $link['host'] = 'Rambler';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://video\.mail\.ru/([a-z]+)/([^/]+)/([0-9]+)/([0-9]+)\.html*%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object width="'.$options['width'].'" height="'.$options['height'].'"><param name="flashvars" value="imaginehost=video.mail.ru&perlhost=my.video.mail.ru&alias='.$m1[1].'&username='.$m1[2].'&albumid='.$m1[3].'&id='.$m1[4].'&catalogurl=http://video.mail.ru/catalog/music/" /><param name="movie" value="http://img.mail.ru/r/video/player_full_size.swf?par=http://video.mail.ru/'.$m1[1].'/'.$m1[2].'/'.$m1[3].'/$'.$m1[4].'$0$248"></param><embed src="http://img.mail.ru/r/video/player_full_size.swf?par=http://video.mail.ru/'.$m1[1].'/'.$m1[2].'/'.$m1[3].'/$'.$m1[4].'$0$248" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'" flashvars="imaginehost=video.mail.ru&perlhost=my.video.mail.ru&alias='.$m1[1].'&username='.$m1[2].'&albumid='.$m1[3].'&id='.$m1[4].'&catalogurl=http://video.mail.ru/catalog/music/"></embed></object>';
      $link['is_video'] = true;
      $link['host'] = 'Mail.Ru';
      $link['vid'] = $m1[1].'/'.$m1[2].'/'.$m1[3].'/'.$m1[4];
    } elseif (preg_match('%^http://rutube\.ru/tracks/(\d+).html\?v=(.+)$%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<OBJECT width="'.$options['width'].'" height="'.$options['height'].'"><PARAM name="movie" value="http://video.rutube.ru/'.$m1[2].'" /><PARAM name="wmode" value="transparent" /><EMBED src="http://video.rutube.ru/'.$m1[2].'" type="application/x-shockwave-flash" wmode="transparent" width="'.$options['width'].'" height="'.$options['height'].'" /></OBJECT>';
      $link['is_video'] = true;
      $link['host'] = 'RuTube';
      $link['vid'] = $m[2];
    }

    if (!empty($link['is_video']) and empty($link['thumbnail']))
      $link['thumbnail'] = 'lib/modules/base/video.png';

    return empty($link) ? null : $link;
  }

  /**
   * Превращает абсолютный путь к файлу в относительный.
   */
  public static function localpath($path)
  {
    if (0 === strpos($path, MCMS_ROOT))
      return substr($path, strlen(MCMS_ROOT) + 1);
    else
      return $path;
  }

  public static function dispatch_rpc($class, Context $ctx)
  {
    $method = 'rpc_'. $ctx->get('action', 'default');

    if (method_exists($class, $method)) {
      if (null === ($result = call_user_func(array($class, $method), $ctx)))
        $result = $ctx->getRedirect();
      return $result;
    }

    return false;
  }

  public static function format($text)
  {
    $lines = preg_split('/[\r\n]+/', $text);
    $text = '<p>'. join('</p><p>', $lines) .'</p>';
    return $text;
  }

  public static function profile($mode, $name = null)
  {
    static $data = array();

    if (!(($ctx = Context::last()) and $ctx->canDebug()))
      return;

    switch ($mode) {
    case 'get':
      return $data;

    case 'start':
      $data[$name] = array(
        'time' => microtime(true),
        'queries' => $ctx->db->getLogSize(),
        );
      break;

    case 'stop':
      $data[$name]['time'] = microtime(true) - $data[$name]['time'];
      $data[$name]['queries'] = $ctx->db->getLogSize() - $data[$name]['queries'];
      break;
    }
  }

  public static function writeFile($filename, $content)
  {
    if (is_array($content)) {
      $content = "<?php // This is a generated file.\n"
        . "return " . var_export($content, true)
        . ";\n";

      // Удаляем числовые ключи из массивов: они, как правило,
      // присвоены автоматически, но усложняют ручное изменение
      // файла.
      $content = preg_replace('/\d+ => /', '', $content);

      // Заставляем массивы открываться на одной строке.
      $content = preg_replace('/ =>\s+array \(/', ' => array (', $content);
    }

    if (file_exists($filename)) {
      if (!is_writable($filename)) {
        if (is_writable(dirname($filename)))
          unlink($filename);
        else
          throw new RuntimeException(t('Изменение файла %file невозможно: он защищён от записи.', array(
            '%file' => basename($filename),
            )));
      }
    }

    if (!@file_put_contents($filename, $content))
      throw new RuntimeException(t('Не удалось записать файл %file.', array(
        '%file' => basename($filename),
        )));
  }

  public static function mkpath(array $elements)
  {
    return join(DIRECTORY_SEPARATOR, $elements);
  }

  public static function renderPager(array $pager)
  {
    $output = '<ul class=\'pager\'>';

    foreach ($pager['list'] as $page => $link)
      $output .= mcms::html('li', mcms::html('a', array(
        'href' => $link,
        'class' => $link ? '' : 'active',
        ), $page));

    $output .= '</ul>';

    return $output;
  }
};

set_exception_handler('mcms::fatal');
set_error_handler('mcms::error_handler', E_ERROR /*|E_WARNING|E_PARSE*/);
register_shutdown_function('mcms::shutdown_handler');
