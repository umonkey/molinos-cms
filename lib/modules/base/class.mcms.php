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

    foreach ($parts as $k => $v) {
      if (!empty($v)) {
        if (is_array($v))
          if ($k == 'class')
            $v = join(' ', $v);
          else {
            $v = null;
          }

        $output .= ' '.$k.'=\''. mcms_plain($v, false) .'\'';
      } elseif ($k == 'value') {
        $output .= " value=''";
      }
    }

    if (null === $content and !in_array($name, array('a', 'script', 'div', 'textarea', 'span'))) {
      $output .= ' />';
    } else {
      $output .= '>'. $content .'</'. $name .'>';
    }

    return $output;
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
    $tmp = mcms::getModuleMap();
    return !empty($tmp['modules'][$name]['enabled']);
  }

  public static function modpath($name)
  {
    return 'lib/modules/'. $name;
  }

  public static function flush($flags = null)
  {
    if (null !== ($cache = BebopCache::getInstance()))
      $cache->flush($flags & self::FLUSH_NOW ? true : false);
  }

  public static function db($name = 'default')
  {
    return PDO_Singleton::getInstance($name);
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
    foreach ($tmp = mcms::getImplementors($interface) as $class)
      if (mcms::class_exists($class))
        call_user_func_array(array($class, $method), $args);
  }

  public static function invoke_module($module, $interface, $method, array &$args = array())
  {
    $res = null;
    $tmp = mcms::getImplementors($interface, $module);

    foreach (mcms::getImplementors($interface, $module) as $class) {
      if (self::class_exists($class))
      $res = call_user_func_array(array($class, $method), $args);
    }

    return $res;
  }

  public static function redirect($path, $status = 301)
  {
    if (!in_array($status, array('301', '302', '303', '307')))
      throw new Exception("Статус перенаправления {$status} не определён в стандарте HTTP/1.1");

    try {
      mcms::db()->commit();
      mcms::flush(mcms::FLUSH_NOW);
    } catch (NotInstalledException $e) {
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
      $status = 303;

    $url = new url($path);
    $target = $url->getAbsolute();
    mcms::log('redirect', $target);

    // При работе с JSON возвращаем адрес.
    bebop_on_json(array(
      'status' => 'redirect',
      'redirect' => $target,
      ));

    header('HTTP/1.1 '. $status .' Redirect');
    header('Location: '. $target);
    exit();
  }

  // Отладочные функции.
  public static function debug()
  {
    if (bebop_is_debugger()) {
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

        if (null !== ($log = mcms::db()->getLog())) {
          $idx = 1;
          printf("\n--- SQL log ---\n");

          foreach ($log as $sql) {
            if (substr($sql, 0, 2) == '--')
              printf("     %s\n", $sql);
            else
              printf("%3d. %s\n", $idx++, $sql);
          }
        }
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

  public static function getImplementors($interface, $module = null)
  {
    static $map = null;

    if (null === $map) {
      $map = self::getModuleMap();
    }

    if (null === $module and array_key_exists($interface, $map['interfaces']))
      return array_unique($map['interfaces'][$interface]); // FIXME: как сюда попадают неуникальные?  Пример: mcms::getImplementors('iContentType');
    elseif (!empty($map['modules'][$module]['implementors'][$interface]))
      return $map['modules'][$module]['implementors'][$interface];

    return array();
  }

  public static function getModuleMap($name = null)
  {
    $result = null;
    $filename = mcms::modmap();

    mcms::mkdir(dirname($filename), t('Каталог для временных файлов (%path) '
      .'отсутствует и не может быть создан.',
      array('%path' => mcms::config('tmpdir'))));

    if (file_exists($filename) and (filemtime($filename) < filemtime('lib/modules')))
      unlink($filename);

    if (file_exists($filename) and is_readable($filename) and filesize($filename)) {
      if (is_array($result = unserialize(file_get_contents($filename))))
        return $result;
    }

    $result = self::getModuleMapScan();

    if (null !== $name)
      return $result['modules'][$name];

    if (is_writable(dirname($filename)))
      file_put_contents($filename, serialize($result));

    return $result;
  }

  private static function getModuleMapScan()
  {
    static $lock = false;

    if ($lock)
      throw new RuntimeException(t('Повторный вход в getModuleMapScan().'));

    $lock = true;

    $enabled = explode(',', mcms::config('runtime_modules'));

    $result = array(
      'modules' => array(),
      'classes' => array(),
      'interfaces' => array(),
      );

    foreach ($modules = glob('lib/modules/*') as $path) {
      $modname = basename($path);

      $result['modules'][$modname] = array(
        'classes' => array(),
        'interfaces' => array(),
        'implementors' => array(),
        'enabled' => $modok = in_array($modname, $enabled),
        );

      if (file_exists($modinfo = MCMS_ROOT .DIRECTORY_SEPARATOR. $path .'/module.info')) {
        if (is_array($ini = parse_ini_file($modinfo, true))) {
          // Копируем базовые свойства.
          foreach (array('group', 'version', 'name', 'docurl') as $k) {
            if (array_key_exists($k, $ini)) {
              $result['modules'][$modname][$k] = $ini[$k];

              if ('group' == $k and 'core' == strtolower($ini[$k]))
                $modok = $result['modules'][$modname]['enabled'] = true;
            }
          }
        }
      }

      // Составляем список доступных классов.
      foreach (glob($path .DIRECTORY_SEPARATOR.'*.php') as $classpath) {
        $parts = explode('.', basename($classpath), 3);

        if (count($parts) != 3 or $parts[2] != 'php')
          continue;

        $classname = null;

        switch ($type = $parts[0]) {
        case 'class':
          $classname = $parts[1];
          break;
        case 'control':
        case 'node':
        case 'widget':
        case 'exception':
          $classname = $parts[1] . $type;
          break;
        case 'interface':
          $classname = 'i'. $parts[1];
          break;
        }

        if (null !== $classname and is_readable($classpath)) {
          // Добавляем в список только первый найденный класс.
          if ($modok and !array_key_exists($classname, $result['classes'])) {
            $result['classes'][$classname] = $classpath;
          }

          // $result['modules'][$modname]['classes'][] = $classname;

          // Строим список интерфейсов.
          if ($type !== 'interface') {
            if (preg_match('@^\s*(abstract\s+){0,1}class\s+([^\s]+)(\s+extends\s+([^\s]+))*(\s+implements\s+([^\n\r]+))*@im', file_get_contents($classpath), $m)) {
              $classname = $m[2];

              $result['modules'][$modname]['classes'][] = $classname;

              if (!empty($m[6]))
                $interfaces = preg_split('/[,\s]+/', preg_replace('#/\*.*\*/#', '', $m[6]), -1, PREG_SPLIT_NO_EMPTY);
              else
                $interfaces = array();

              if (!empty($m[4])) {
                switch ($m[4]) {
                case 'Control':
                  $interfaces[] = 'iFormControl';
                  break;
                case 'Widget':
                  $interfaces[] = 'iWidget';
                  break;
                case 'Node':
                case 'NodeBase':
                  $interfaces[] = 'iContentType';
                  break;
                }
              }

              foreach ($interfaces as $i) {
                if (!in_array($i, $result['modules'][$modname]['interfaces']))
                  $result['modules'][$modname]['interfaces'][] = $i;
                $result['modules'][$modname]['implementors'][$i][] = $classname;

                if ($modok)
                  $result['interfaces'][$i][] = $classname;
              }
            } else {
              // mcms::log('modscanner', "No suitable class in ". $classpath);
            }
          }
        }
      }

      if (empty($result['modules'][$modname]['classes']))
        unset($result['modules'][$modname]);
    }

    ksort($result['classes']);

    $lock = false;

    return $result;
  }

  public static function enableModules(array $list)
  {
    $tmp = Config::getInstance();
    $tmp->set('modules', join(',', $list), 'runtime');
    $tmp->write();

    if (file_exists($tmp = mcms::modmap()))
      unlink($tmp);
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
        $result['list'][$i] = ($i == $current) ? '' : strval($url);
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

    if (!bebop_is_debugger())
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
    return MsgModule::send($from, $to, $subject, $text);
  }

  public static function version($mode = mcms::VERSION_CURRENT)
  {
    static $version = null;

    if (null === $version) {
      if (is_readable($fname = 'lib/version.info'))
        $version = trim(file_get_contents($fname));
      else
        $version = 'unknown.trunk';
    }

    switch ($mode) {
    case self::VERSION_CURRENT:
      return $version;

    case self::VERSION_RELEASE:
      return substr($version, 0, - strlen(strrchr($version, '.')));

    case self::VERSION_AVAILABLE:
      $release = self::version(self::VERSION_RELEASE);
      $content = mcms_fetch_file('http://code.google.com/p/molinos-cms/downloads/list?q=label:R'. $release);

      if (preg_match($re = "@http://molinos-cms\.googlecode\.com/files/molinos-cms-({$release}\.[0-9]+)\.zip@", $content, $m))
        return $m[1];
      else
        return $version;

    case self::VERSION_AVAILABLE_URL:
      return 'http://molinos-cms.googlecode.com/files/molinos-cms-'. self::version(self::VERSION_AVAILABLE) .'.zip';
    }

    return $version;
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

    if (bebop_is_debugger()) {
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
      /*
      if (class_exists('PDO_Singleton', false))
        mcms::db()->rollback();
      */
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

        if (bebop_is_debugger())
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

    // Проталкиваем jQuery на первое место.
    // FIXME: нужно более вменяемое решение.
    self::pop($extras, 'lib/modules/tinymce/editor/tiny_mce_gzip.js');
    self::pop($extras, 'lib/modules/tinymce/editor/tiny_mce_src.js');
    self::pop($extras, 'lib/modules/tinymce/editor/tiny_mce.js');
    self::pop($extras, 'themes/all/jquery/jquery.min.js');
    self::pop($extras, 'themes/all/jquery/jquery.js');

    $compress = (mcms::ismodule('compressor') and empty($_GET['nocompress']));

    // Заход первый: выводим некомпрессируемые объекты
    // или все объекты, если нет компрессора.
    foreach ($extras as $file => $ok) {
      if (!$ok or !$compress) {
        if ('.js' == substr($file, -3))
          $output .= mcms::html('script', array(
            'type' => 'text/javascript',
            'src' => $file,
            )) ."\n";
        elseif ('.css' == substr($file, -4))
          $output .= mcms::html('link', array(
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => $file,
            )) ."\n";
      }
    }

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
   * Базовый вывод сообщения об ошибке.
   *
   * Формирует простой HTML код с описанием ошибки.  Отладчики при этом видят
   * стэк вызова.
   *
   * @return void
   */
  public static function renderException(Exception $e)
  {
    $message = $e->getMessage();
    $backtrace = mcms::backtrace($e);

    if (!bebop_is_debugger())
      $backtrace = null;

    bebop_on_json(array(
      'status' => 'error',
      'message' => $message,
      ));

    if (ob_get_length())
      ob_end_clean();

    if (!empty($_SERVER['REQUEST_METHOD'])) {
      header('HTTP/1.1 500 Internal Server Error');
      header("Content-Type: text/html; charset=utf-8");

      $html = '<html><head><title>Internal Server Error</title></head><body>'
        .'<h1>Internal Server Error</h1><p>'. $message .'</p>';

      if (null !== $backtrace)
        $html .= '<h2>Стэк вызова</h2><pre>'. $backtrace .'</pre>';

      $html .= '<hr/>'. self::getSignature();
      $html .= '</body></html>';

      header('Content-Length: '. strlen($html));
      die($html);
    }

    print $message ."\n\n";

    if (!empty($_SERVER['REMOTE_ADDR'])) {
      printf("--- backtrace (time: %s) ---\n", microtime());
      print $backtrace;
    }

    die();
  }

  public static function modmap($prefix = 'modmap')
  {
    return mcms::config('tmpdir') .'/'. $prefix .'.'.
      md5(MCMS_ROOT .','.  $_SERVER['HTTP_HOST']);
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

  private static function getSignature()
  {
    $ctx = new Context();

    $at = mcms::html('a', array(
      'href' => $ctx->url()->getBase($ctx),
      ), $ctx->host() . $ctx->folder());

    $sig = '<em>Molinos CMS v'. mcms::version()
      .' at '. $at .'</em>';

    return $sig;
  }

  public static function run(Context $ctx = null)
  {
    self::check();

    // Определение текущего урла.
    if (!empty($_GET['__cleanurls'])) {
      $parts = explode('?', $_SERVER['REQUEST_URI']);
      $url = $parts[0] .'?'. $_SERVER['QUERY_STRING'];
    } else {
      $url = $_SERVER['REQUEST_URI'];
    }

    if (null === $ctx)
      $ctx = new Context(array('url' => $url));

    try {
      $req = new RequestController($ctx);
      $output = $req->run();
    } catch (UserErrorException $e) {
      if (mcms::config('debug_errors'))
        mcms::fatal($e);

      if ('errors' == $ctx->debug())
        mcms::fatal($e);

      if ($e->getCode()) {
        // Ошибка 404 — пытаемся использовать подстановку.
        if (404 == $e->getCode()) {
          try {
            $new = mcms::db()->getResult("SELECT `new` FROM `node__fallback` "
              ."WHERE old = ?", array($_SERVER['REQUEST_URI']));
            if (!empty($new))
              mcms::redirect($new, 302);
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
              '@url' => strval($url),
              )));
        }

        header(sprintf('HTTP/1.1 %s %s', $e->getCode(),
          self::getHttpStatusMessage($e->getCode())));
      } else {
        throw $e;
      }
    }

    if ('profile' == $ctx->debug())
      mcms::debug('Profiling.');

    mcms::fixurls($output, true);
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
    if (!empty($_GET['__cleanurls'])) {
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
      $link['type'] = 'audio/mpeg';
      $link['is_audio'] = true;
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

    return empty($link) ? null : $link;
  }
};

set_exception_handler('mcms::fatal');
set_error_handler('mcms::error_handler', E_ERROR /*|E_WARNING|E_PARSE*/);
register_shutdown_function('mcms::shutdown_handler');
