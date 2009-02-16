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
  const VERSION_STABLE = 2;

  private static $extras = array();

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
    if (count($nodes) == 1 or !class_exists('XspfModule'))
      $playlist = '?q=' . urlencode($firstfile);
    else
      $playlist = '?q=playlist.rpc&nodes='. join(',', $nodes);

    // Параметризация проигрывателя.
    $options = array_merge(array(
      'file' => mcms::path() . '/' . $playlist,
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

    $params = html::em('param', array(
      'name' => 'movie',
      'value' => $url,
      ));
    $params .= html::em('param', array(
      'name' => 'wmode',
      'value' => 'transparent',
      ));

    return html::em('object', array(
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

  public static function config($key, $default = null)
  {
    if (!class_exists('Config'))
      self::fatal('Отсутствует поддержка конфигурационных файлов.');

    $config = Context::last()->config;

    return isset($config->$key)
      ? $config->$key
      : $default;
  }

  public static function modconf($modulename, $key = null, $default = null)
  {
    $conf = Structure::getInstance()->getModuleConf($modulename);

    if (null !== $key)
      return empty($conf[$key]) ? $default : $conf[$key];
    else
      return $conf;
  }

  public static function flush($flags = null)
  {
    if (null !== ($cache = BebopCache::getInstance()))
      $cache->flush($flags & self::FLUSH_NOW ? true : false);
  }

  public static function invoke($interface, $method, array $args = array())
  {
    $res = array();

    foreach (Loader::getImplementors($interface) as $class)
      if (class_exists($class))
        $res[] = call_user_func_array(array($class, $method), $args);

    return $res;
  }

  public static function invoke_module($module, $interface, $method, array &$args = array())
  {
    $res = false;

    foreach (Loader::getImplementors($interface, $module) as $class) {
      if (class_exists($class) and method_exists($class, $method)) {
        $args[] = $class;
        $res = call_user_func_array(array($class, $method), $args);
        array_pop($args);
      }
    }

    return $res;
  }

  // Отладочные функции.
  public static function debug()
  {
    if (true /* empty($_SERVER['HTTP_HOST']) or ($ctx = Context::last()) and $ctx->canDebug() */) {
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
      }

      die();
    }
  }

  public static function log($op, $message, $nid = null)
  {
    if (class_exists('SysLogmodule'))
      SysLogModule::log($op, $message, $nid);
    else
      self::flog($op, $message);
  }

  public static function flog($message)
  {
    $prefix = '';

    if (is_array($trace = debug_backtrace()) and count($trace) >= 2) {
      $prefix = empty($trace[1]['class'])
        ? $trace[1]['function']
        : $trace[1]['class'] . $trace[1]['type'] . $trace[1]['function'] . '()';

      $prefix = '[' . $prefix . '] ';
    }

    if ($message instanceof Exception)
      $message = get_class($message) . ': ' . $message->getMessage();

    error_log($prefix . $message, 0);
  }

  // Возвращает список доступных классов и файлов, в которых они описаны.
  // Информация о классах кэшируется в tmp/.classes.php или -- если доступен
  // класс BebopCache -- в более быстром кэше.
  public static function getClassMap()
  {
    $tmp = self::getModuleMap();
    return $tmp['classes'];
  }

  public static function pager($total, $current, $limit, $paramname = 'page', $default = 1)
  {
    $result = array();
    $list = '';

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

      $plinks = array();
      for ($i = $beg; $i <= $end; $i++) {
        $url->setarg($paramname, ($i == $default) ? '' : $i);
        $plinks[$i] = ($i == $current) ? '' : $url->string();
        $list .= html::em('page', array(
          'number' => $i,
          'link' => $plinks[$i],
          ));
      }

      if (!empty($plinks[$current - 1]))
        $result['prev'] = $plinks[$current - 1];
      if (!empty($plinks[$current + 1]))
        $result['next'] = $plinks[$current + 1];
    }

    return html::em('pager', $result, $list);
  }

  public static function fatal()
  {
    $report = new CrashReport(func_get_args());
    $report->send();
    $report->show();
    die();
  }

  private static function fatalSendReport($message, array $extras, array $backtrace)
  {
    mcms::debug($message, $extras, $backtrace);
  }

  private static function writeCrashDump($contents)
  {
    // Узнаем, куда же складывать дампы.
    // Если такой директории нет, пытаемся создать.
    $dumpdir = mcms::mkdir(mcms::config('dumpdir', os::path('tmp', 'crashdump')));

    // Задаем файл для выгрузки дампа и проверяем на наличие,
    // если существует - добавляем случайный мусор в название.
    $dumpfile = os::path($dumpdir, date('Y-m-d-') . md5(serialize($_SERVER)));
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
      $parts = explode('.', MCMS_VERSION);
      $release = $parts[0] . '.' . $parts[1];
      $release = substr($release, 0, strspn($release, '0123456789.'));
      return $release;

    case self::VERSION_STABLE:
      return substr(MCMS_VERSION, 0, strspn(MCMS_VERSION, '0123456789.'));
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
      /*
      if (!empty($v['file']))
        $v['file'] = preg_replace('@.*'. preg_quote($libdir) .'@', $libdir, $v['file']);

      if (!empty($v['class']))
        $func = $v['class'] .$v['type']. $v['function'];
      else
        $func = $v['function'];
      */

      $output .= sprintf("%2d. ", $k + 1);
      $output .= mcms::formatStackElement($v);

      /*
      if (!empty($v['file']) and !empty($v['line']))
        $output .= sprintf('%s(%d) — ', ltrim(str_replace(MCMS_ROOT, '', $v['file']), '/'), $v['line']);
      else
        $output .= '??? — ';

      $output .= $func .'()';
      */

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

      $r = new Response($output, 'text/plain', 500);
      $r->send();
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

        $message = template::renderClass(__CLASS__, array(
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

        $output = "Случилось что-то страшное.  Администрация сервера поставлена в известность, скоро всё должно снова заработать.\n";

        if (($ctx = Context::last()) and $ctx->canDebug())
          $output .= sprintf("\n--- 8< --- Отладочная информация --- 8< ---\n\n"
            ."Код ошибки: %s\n"
            ."   Локация: %s(%s)\n"
            ." Сообщение: %s\n"
            ."    Версия: %s\n",
            $e['type'], ltrim(str_replace(MCMS_ROOT, '', $e['file']), '/'), $e['line'], $e['message'], mcms::version());

        $r = new Response($output, 'text/plain', 500);
        $r->send();
      }
    }
  }

  public static function mkdir($path, $msg = null)
  {
    if (!is_dir($path) and !mkdir($path, 0775, true)) {
      if (null === $msg)
        $msg = 'Каталог %path отсутствует и не может быть создан.';
      throw new RuntimeException(t($msg, array(
        '%path' => $next,
        )));
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

    $output = html::em('script', array(
      'type' => 'text/javascript',
      ), 'var mcms_path = \''. $root .'\';') ."\n";

    foreach ($extras as $k => $v) {
      if (0 === strpos($k, 'script:')) {
        $output .= html::em('script', array(
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
          $js .= html::em('script', array(
            'type' => 'text/javascript',
            'src' => $file,
            )) ."\n";
        elseif ('.css' == substr($file, -4))
          $css .= html::em('link', array(
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

    mcms::flog($msg = 'deprecated function '
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

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

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

  public static function getSignature(Context $ctx = null, $full = false)
  {
    $result = array(
      'version' => mcms::version(),
      'client' => $_SERVER['REMOTE_ADDR'],
      );

    try {
      if (null === $ctx)
        $ctx = new Context();

      $result['at'] = $ctx->host() . $ctx->folder();
      $result['version_link'] = 'http://code.google.com/p/molinos-cms/wiki/ChangeLog_' . str_replace('.', '_', mcms::version(mcms::VERSION_STABLE));

      if ($full) {
        $options = array();

        if (count($parts = explode(':', mcms::config('db'))))
          if (in_array($parts[0], PDO_Singleton::listDrivers()))
            $options[] = $parts[0];

        $options[] = str_replace('_provider', '', get_class(BebopCache::getInstance()));
        $options[] = ini_get('memory_limit');

        $result['options'] = join('+', $options);
      }
    }

    catch (Exception $e) {
    }

    return $result;
  }

  public static function getSignatureXML(Context $ctx = null)
  {
    $sig = self::getSignature($ctx, true);
    $sig['name'] = 'signature';

    return html::em('block', $sig);
  }

  public static function run(Context $ctx = null)
  {
    try {
      if (null === $ctx)
        $ctx = new Context(array(
          'url' => defined('MCMS_REQUEST_URI')
            ? MCMS_REQUEST_URI
            : '?' . $_SERVER['QUERY_STRING'],
          ));

      $ctx->checkEnvironment();

      if (!$ctx->config->isok() and 'install.rpc' != $ctx->query() and class_exists('InstallModule'))
        $ctx->redirect('?q=install.rpc');

      $request = new Request();
      $response = $request->process($ctx);

      $response->send();
    }

    catch (Exception $e) {
      $output = $e->getMessage() . "\n\n" . mcms::backtrace($e);
      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: ' . strlen($output));
      die($output);
    }
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
    }

    return empty($link) ? null : $link;
  }

  public static function dispatch_rpc($class, Context $ctx, $default = 'default')
  {
  }

  public static function format($text)
  {
    $lines = preg_split('/[\r\n]+/', $text);
    $text = '<p>'. join('</p><p>', $lines) .'</p>';
    return $text;
  }

  public static function mkpath(array $elements)
  {
    return join(DIRECTORY_SEPARATOR, $elements);
  }

  public static function renderPager(array $pager)
  {
    $output = '<ul class=\'pager\'>';

    foreach ($pager['list'] as $page => $link)
      $output .= html::em('li', html::em('a', array(
        'href' => $link,
        'class' => $link ? '' : 'active',
        ), $page));

    $output .= '</ul>';

    return $output;
  }

  /**
   * Транслитерация.
   */
  public static function translit($string)
  {
    $xlat = array(
      'а' => 'a',
      'б' => 'b',
      'в' => 'v',
      'г' => 'g',
      'д' => 'd',
      'е' => 'e',
      'ё' => 'e',
      'ж' => 'zh',
      'з' => 'z',
      'и' => 'i',
      'й' => 'j',
      'к' => 'k',
      'л' => 'l',
      'м' => 'm',
      'н' => 'n',
      'о' => 'o',
      'п' => 'p',
      'р' => 'r',
      'с' => 's',
      'т' => 't',
      'у' => 'u',
      'ф' => 'f',
      'х' => 'h',
      'ц' => 'c',
      'ч' => 'ch',
      'ш' => 'sh',
      'щ' => 'sch',
      'ы' => 'y',
      'э' => 'e',
      'ю' => 'yu',
      'я' => 'ya',
      );

    return str_replace(array_keys($xlat), array_values($xlat), mb_strtolower($string));
  }

  /**
   * Форматирование элемента стэка.
   */
  public static function formatStackElement(array $em)
  {
    $output = '';

    if (!empty($em['file']))
      $output .= os::localPath($em['file']) . '(' . $em['line'] . ') — ';

    $caller = empty($em['class'])
      ? ''
      : $em['class'];
    $caller .= empty($em['type'])
      ? ''
      : $em['type'];
    if (!empty($em['function'])) {
      $caller .= $em['function'] . '(';
      if (!empty($em['args'])) {
        $args = array();
        foreach ($em['args'] as $arg) {
          if (is_array($arg))
            $args[] = 'array';
          elseif (is_object($arg))
            $args[] = get_class($arg);
          elseif (true === $arg)
            $args[] = 'true';
          elseif (false === $arg)
            $args[] = 'false';
          elseif (null === $arg)
            $args[] = 'null';
          elseif (is_string($arg)) {
            $tmp = '"';
            $tmp .= (mb_strlen($arg) > 10)
              ? mb_substr($arg, 0, 10) . '...'
              : $arg;
            $tmp .= '"';
            $args[] = $tmp;
          } else
            $args[] = $arg;
        }
        $caller .= join(', ', $args);
      }
      $caller .= ');';
    }

    $output .= $caller;

    return $output;
  }
};

class CrashReport
{
  private $stack;
  private $message;
  private $args;
  private $_send;

  public function __construct(array $args)
  {
    $this->_send = true;

    if (null === ($e = array_shift($args)))
      $this->message = 'Unknown fatal error.';

    elseif ($e instanceof Exception) {
      $this->message = $e->getMessage();
      if ($e instanceof UserErrorException) {
        $this->_send = false;
        $this->message = 'Ошибка ' . $e->getCode() . ': ' . $this->message;
      }
      $this->stack = $e->getTrace();
      array_unshift($this->stack, array(
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'function' => 'throw new ' . get_class($e),
        ));
    }

    else {
      $this->message = $e;
      $this->stack = debug_backtrace();
      array_shift($this->stack);
    }

    // Удаляем лишние пути.
    foreach ($this->stack as $k => $v)
      if (!empty($v['file']))
        $this->stack[$k]['file'] = os::localPath($v['file']);

    $this->args = $args;
  }

  public function show()
  {
    $html = '<html><head><title>Fatal Error</title></head>'
      . '<body>'
      . '<h1>Fatal Error</h1><p>'. $this->message .'</p>';

    if (null !== $this->stack)
      $html .= '<h2>Стэк вызова</h2><pre>'. mcms::backtrace($this->stack) .'</pre>';

    $sig = mcms::getSignature();
    $html .= sprintf('<hr/><em><a href="%s">Molinos CMS v%s</a> at <a href="http://%s/">%s</a> for %s</em>',
        $sig['version_link'], $sig['version'], $sig['at'], $sig['at'], $sig['client']);

    $html .= '</body></html>';

    if (ob_get_length())
      ob_end_clean();

    header('HTTP/1.1 500 FUBAR');
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Length: ' . strlen($html));
    print $html;
  }

  public function send()
  {
    if (!$this->_send)
      return;

    $to = mcms::config('backtracerecipients', 'cms-bugs@molinos.ru');

    if (class_exists('BebopMimeMail') and !empty($to)) {
      $output = html::em('p', $this->message);

      $output .= html::em('p', 'HOST: ' . $_SERVER['HTTP_HOST']);
      $output .= html::em('p', 'METHOD: ' . $_SERVER['REQUEST_METHOD']);
      $output .= html::em('p', 'URI: ' . $_SERVER['REQUEST_URI']);

      if (!empty($this->stack))
        $output .= html::em('pre', mcms::backtrace($this->stack));

      if (!empty($_POST))
        $output .= html::em('pre', html::cdata('$_POST = ' . var_export($_POST, true) . ';'));

      $subject = 'Molinos CMS v' . MCMS_VERSION . ' crashed at ' . $_SERVER['HTTP_HOST'];
      BebopMimeMail::send(null, $to, $subject, $output);
    }
  }
}

set_exception_handler('mcms::fatal');
set_error_handler('mcms::error_handler', E_ERROR /*|E_WARNING|E_PARSE*/);
register_shutdown_function('mcms::shutdown_handler');
