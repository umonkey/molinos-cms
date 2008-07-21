<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
      'img' => 'src',
      'a' => 'href',
      'form' => 'action',
      'script' => 'src',
      'link' => 'href',
      );

    // Прозрачная поддержка чистых урлов.
    foreach ($fixmap as $k => $v) {
      if ($k != $name or !array_key_exists($v, $parts))
        continue;

      if (false !== strstr($parts[$v], '://'))
        continue;

      if ('/' == substr($parts[$v], 0, 1))
        continue;

      if (is_readable(MCMS_ROOT .'/'. $parts[$v]))
        continue;

      if ('form' == $k)
        $url = mcms::path() .'/'. strval(new url($parts[$v]));
      else
        $url = strval(new url($parts[$v]));

      $parts[$v] = $url;
    }

    if (null !== $parts) {
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
      if ($v instanceof Node)
        $v = $v->getRaw();

      switch ($v['filetype']) {
      case 'audio/mpeg':
        if ($types & self::MEDIA_AUDIO) {
          $nodes[] = $v['id'];
          $havetypes |= self::MEDIA_AUDIO;
          if (null === $firstfile)
            $firstfile = "attachment/{$v['id']}/{$v['filename']}";
        }
        break;
      case 'video/flv':
      case 'video/x-flv':
        if ($types & self::MEDIA_VIDEO) {
          $nodes[] = $v['id'];
          $havetypes |= self::MEDIA_VIDEO;
          if (null === $firstfile)
            $firstfile = "attachment/{$v['id']}/{$v['filename']}";
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
      $playlist = l('playlist.rpc?nodes='. join(',', $nodes));

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

    switch (count($args)) {
    case 1:
      $result = $cache->$args[0];
      break;
    case 2:
      $cache->$args[0] = $args[1];
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
    $fname = mcms::config('tmpdir') .'/'. $key;

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
    if (!class_exists('BebopConfig'))
      self::fatal('Отсутствует поддержка конфигурационных файлов.');

    return isset(BebopConfig::getInstance()->$key)
      ? BebopConfig::getInstance()->$key
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
    if ($url->islocal and substr($path, 0, 1) != '/')
      $path = mcms::path() .'/'. strval($url);

    $target = $path;

    /*
    mcms::debug($path, $url, $url->path, mcms::path());

    // Относительные ссылки на CMS.
    if (empty($url->host) and '/' != substr($url->path, 0, 1)) {
      $target = mcms::path() . strval($url);
    } else {
      $target = strval($url);
    }
    */

    // При редиректе на текущую страницу добавляем случайное число,
    // без этого Опера не редиректит.
    if ($target == $_SERVER['REQUEST_URI'])
      $target .= ((false == strstr($target, '?')) ? '?' : '&') .'rnd='. mt_rand();

    mcms::log('redirect', $target);

    // При работе с JSON возвращаем адрес.
    bebop_on_json(array(
      'status' => 'redirect',
      'redirect' => $target,
      ));

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
          $output[] = preg_replace('/ =>\s+/', ' => ', var_export($arg, true));
        }
      } else {
        $output[] = 'breakpoint';
      }

      bebop_on_json(array('args' => $output));

      if (ob_get_length())
        ob_end_clean();

      if (!empty($_SERVER['REQUEST_METHOD']))
        header("Content-Type: text/plain; charset=utf-8");

      print join(";\n\n", $output) .";\n\n";

      if (true /* !empty($_SERVER['REMOTE_ADDR']) */) {
        printf("--- backtrace (time: %s, duratoin: %s) ---\n", microtime(),
          microtime(true) - MCMS_START_TIME);
        print mcms::backtrace();

        if (null !== ($log = mcms::db()->getLog())) {
          $idx = 1;
          printf("\n--- SQL log ---\n");

          foreach ($log as $sql)
            if (substr($sql, 0, 2) != '--')
              printf("%3d. %s\n", $idx++, $sql);
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
        '%user' => mcms::user()->name,
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
    $filename = mcms::config('tmpdir') .'/.modmap.php';

    mcms::mkdir(dirname($filename));

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
      mcms::fatal(t('Повторный вход в getModuleMapScan().'));

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
    $tmp = BebopConfig::getInstance();
    $tmp->set('modules', join(',', $list), 'runtime');
    $tmp->write();

    if (file_exists($tmp = 'tmp/.modmap.php'))
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

  public static function pager($total, $current, $limit, $paramname = 'page', $default = 1)
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
      $url = bebop_split_url();

      for ($i = $beg; $i <= $end; $i++) {
        $url['args'][$paramname] = ($i == $default) ? '' : $i;
        $result['list'][$i] = ($i == $current) ? '' : bebop_combine_url($url);
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
    $output = array();

    foreach (func_get_args() as $arg) {
      $output[] = var_export($arg, true);
    }

    bebop_on_json(array('args' => $output));

    if (ob_get_length())
      ob_end_clean();

    if (!empty($_SERVER['REQUEST_METHOD']))
      header("Content-Type: text/plain; charset=utf-8");

    $o = join(";\n\n", $output) .";\n\n";
    print $o;
    // mcms::log("error.fatal", $o);

    if (!empty($_SERVER['REMOTE_ADDR'])) {
      printf("--- backtrace (time: %s) ---\n", microtime());
      print mcms::backtrace();
    }

    die();
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
        $version = file_get_contents($fname);
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
        $v['file'] = preg_replace('@.*'. $libdir .'@', $libdir, $v['file']);

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

  // Обработчики ошибок.
  public static function eh(Exception $e)
  {
    if (ob_get_length())
      ob_end_clean();

    $str1 = $str2 = $str3 = $str4 = '';
    header('Content-Type: text/plain; charset=utf-8');
    $str1 = sprintf("%s: %s\n", get_class($e), $e->getMessage());
    print($str1);

    if ($e instanceof UserErrorException) {
      $str2 = sprintf("Description: %s\n", $e->getNote());
      print($str2);
    }

    if ($e instanceof TableNotFoundException) {
      if (null !== ($tmp = $e->getQuery())) {
        $str3 = sprintf("\nSQL:    %s\n", $tmp);
        print($str3);
      }
      if (null !== ($tmp = $e->getParams())) {
        $str4 = sprintf("Params: %s\n", preg_replace('/\s*[\n\r]+\s*/', ' ', var_export($tmp, true)));
        print($str4);
      }
    }
    mcms::log(get_class($e), "{$str1}{$str2}{$str3}{$str4}");
    printf("\nLocation: %s(%d)\n", ltrim(str_replace(MCMS_ROOT, '', $e->getFile()), '/'), $e->getLine());

    // print $message;

    printf("\n--- backtrace (time: %s, duratoin: %s) ---\n", microtime(),
        microtime(true) - MCMS_START_TIME);
    print mcms::backtrace($e);

    exit();
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

        $message = t('<p>На сайте <a href=\'@url\'>%host</a> возникла <em>фатальная</em> ошибка #%code в строке %line файла <code>%file</code>.  Текст ошибки: %text.</p><p>Стэк вызова, к сожалению, <a href=\'@function\'>недоступен</a>.</p><p>Molinos.CMS v%version — <a href=\'@changelog\'>ChangeLog</a> | <a href=\'@issues\'>Issues</a></p>', array(
          '%host' => $_SERVER['HTTP_HOST'],
          '@url' => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
          '%code' => $e['type'],
          '%line' => $e['line'],
          '%file' => ltrim(str_replace(MCMS_ROOT, '', $e['file']), '/'),
          '%text' => $e['message'],
          '%version' => mcms::version(),
          '@changelog' => "http://code.google.com/p/molinos-cms/wiki/ChangeLog_". str_replace('.', '', $release),
          '@issues' => "http://code.google.com/p/molinos-cms/issues/list?q=label:Milestone-R". $release,
          '@function' => 'http://docs.php.net/manual/en/function.register-shutdown-function.php',
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
      $path = empty($_GET['__rootpath'])
        ? dirname($_SERVER['SCRIPT_NAME'])
        : $_GET['__rootpath'];

      if ('/' == ($path = '/'. trim($path, '/')))
        $path = '';
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
};

set_exception_handler('mcms::eh');
set_error_handler('mcms::error_handler', E_ERROR /*|E_WARNING|E_PARSE*/);
register_shutdown_function('mcms::shutdown_handler');
