<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

function bebop_redirect($path, $status = 301)
{
    if (is_array($path))
      $path = bebop_combine_url($path, false);
    else
      $path = l($path);

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
      $status = 303;

    if (empty($_GET['reload'])) {
      if (!in_array($status, array('301', '302', '303', '307')))
        throw new Exception("Статус перенаправления {$status} не определён в стандарте HTTP/1.1");

      mcms::db()->commit();
      mcms::flush(mcms::FLUSH_NOW);
    }

    if (substr($path, 0, 1) == '/') {
      $proto = 'http'.((array_key_exists('HTTPS', $_SERVER) and $_SERVER['HTTPS'] == 'on') ? 's' : '');
      $domain = $_SERVER['HTTP_HOST'];
      $path = $proto.'://'.$domain.$path;
    }

    // Если нас вызвали через AJAX, просто возвращаем адрес редиректа.
    if (!empty($_POST['ajax']))
      exit($path);

    header('Location: '. $path);
    exit();
}

// Проверяет, является ли пользователь отладчиком.
function bebop_is_debugger()
{
  static $skip = false;

  if ($skip === false) {
    if (empty($_SERVER['REQUEST_METHOD']))
      $skip = false;

    elseif (!empty($_SESSION['user']['groups']) and in_array('Developers', $_SESSION['user']['groups']))
      $skip = false;

    else {
      $tmp = mcms::config('debuggers');

      if (empty($tmp))
        $skip = true;
      elseif (!in_array($_SERVER['REMOTE_ADDR'], $list = preg_split('/[, ]+/', $tmp)))
        $skip = true;
    }
  }

  return !$skip;
}

function bebop_skip_checks()
{
  if ($_SERVER['SCRIPT_NAME'] == '/install.php')
    return true;
  return false;
}

// Выводит содержимое параметров и стэк вызова, если пользователь является
// отладчиком (ip в конфиге) или состоит в группе Developers.
function bebop_debug()
{
  if (bebop_is_debugger()) {
    // mcms::db()->rollback();

    if (ob_get_length())
      ob_end_clean();

    $output = array();

    if (func_num_args()) {
      foreach (func_get_args() as $arg) {
        $output[] = var_export($arg, true);
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

    if (!empty($_SERVER['REMOTE_ADDR'])) {
      printf("--- backtrace (time: %s) ---\n", microtime());
      print mcms::backtrace();
    }

    die();
  }
}

// Разбивает текущий запрос на составляющие.
function bebop_split_url($url = null)
{
  if ($url === null)
    $url = $_SERVER['REQUEST_URI'];

  $tmp = parse_url($url);

  if (array_key_exists('query', $tmp)) {
    $tmp['args'] = parse_request_args($tmp['query']);
    unset($tmp['query']);
  } else {
    $tmp['args'] = array();
  }

  if (!empty($tmp['args']['q'])) {
    $tmp['path'] = $tmp['args']['q'];
    unset($tmp['args']['q']);
  }

  return $tmp;
}

function parse_request_args($string)
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

// Заворачивает результат работы предыдущей функции обратно.
function bebop_combine_url(array $url, $escape = true)
{
  $result = '';

  if (!mcms::config('handler')) {
    $url['args']['q'] = $url['path'];
    $url['path'] = '/index.php';
  }

  $forbidden = array('nocache', 'flush', 'reload');

  if (bebop_is_json())
    $forbidden[] = 'widget';

  // Если текущий хост отличается от нужного -- делаем абсолютную ссылку.
  if (!empty($url['host']) and ($_SERVER['HTTP_HOST'] != $url['host'] or !empty($url['#absolute']) or in_array('absolute', $url['args'])))
    $result .= 'http://'. $url['host'];

  if (strstr($url['path'], '#') !== false) {
    $parts = explode('#', $url['path']);
    $url['path'] = $parts[0];
    $url['anchor'] = $parts[1];
  }

  $result .= $url['path'];

  if (!empty($url['args'])) {
    $pairs = array();

    ksort($url['args']);

    foreach ($url['args'] as $k => $v) {
      if ($v === null)
        continue;

      elseif (is_array($v)) {
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

      elseif ($v !== '' and !in_array($k, $forbidden))
        $pairs[] = $k .'='. urlencode($v);
    }

    if (!empty($pairs))
      $result .= '?'. join('&', $pairs);
  }

  if ($escape)
    $result = mcms_plain($result);

  if (!empty($url['anchor']))
    $result .= '#'. $url['anchor'];

  return $result;
}

// Возвращает отформатированную ссылку.
function l($url, $title = null, array $options = null)
{
  if (empty($url))
    throw new RuntimeException(t('Не указана ссылка для l().'));
  elseif (!is_string($url))
    throw new RuntimeException(t('Ссылка для l() должна быть строкой.'));

  if (stripos($url, 'install.php')) 
	   return $url;
	   
  $url = bebop_split_url($url);

  foreach (array('smarty.debug', 'flush', 'nocache') as $k)
    if (array_key_exists($k, $url['args']))
      unset($url['args'][$k]);

  $options['href'] = bebop_combine_url($url, false);

  if (null === $title)
    return $options['href'];

  return mcms::html('a', $options, $title);
}

// Формирует дерево из связки по parent_id.
function bebop_make_tree($data, $id, $parent_id, $children = 'children')
{
  // Здесь будем хранить ссылки на все элементы списка.
  $map = array();

  // Здесь будет идентификатор корневого объекта.
  $root = null;

  // Перебираем все данные.
  foreach ($data as $k => $row) {
    // Запоминаем корень.
    if ($root === null)
      $root = intval($row[$id]);

    // Родитель есть, добавляем к нему.
    if (array_key_exists($row[$parent_id], $map))
        $map[$row[$parent_id]][$children][] = &$data[$k];

    // Добавляем все элементы в список.
    $map[$row[$id]] = &$data[$k];
  }

  // Возвращаем результат.
  return (array)@$map[$root];
}

function t($message, array $argv = array())
{
  /*
  // TODO lang detection
  $lang = 'ru';

  static $sth = null;

  if (null == $sth)
    $sth = mcms::db()->prepare("SELECT m2.* FROM `node__messages` m1 LEFT JOIN `node__messages` m2 ON m1.id = m2.id WHERE m2.lang = :lang AND m1.message = :message");

  $sth->execute(array(
    ':lang' => $lang,
    ':message' => $message,
  ));

  $result = $sth->fetchColumn(2);

  if (false !== $result)
    $message = str_replace($message, $result, $message);
  */

  foreach ($argv as $k => $v) {
    switch (substr($k, 0, 1)) {
    case '!':
    case '%':
      $message = str_replace($k, $v, $message);
      break;
    case '@':
      $message = str_replace($k, l($v), $message);
      break;
    }
  }

  return $message;
}

function bebop_is_json()
{
  return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

// Возвращает массив в виде JSON.
function bebop_on_json(array $result)
{
  if (bebop_is_json()) {
    mcms::db()->commit();
    mcms::flush(mcms::FLUSH_NOW);

    setlocale(LC_ALL, "en_US.UTF-8");

    $output = json_encode($result);
    header('Content-Type: application/x-json');
    header('Content-Length: '. strlen($output));
    die($output);
  }
}

// Применяет шаблон к данным.
function bebop_render_object($type, $name, $theme = null, $data)
{
  $__root = $_SERVER['DOCUMENT_ROOT'];

  if (null === $theme) {
    $ctx = RequestContext::getGlobal();
    $theme = $ctx->theme;
  }

  if ($data instanceof Exception) {
    $data = array('error' => array(
      'code' => $data->getCode(),
      'class' => get_class($data),
      'message' => $data->getMessage(),
      'description' => $data->getMessage(),
      ));
  } elseif (!is_array($data)) {
    $data = array($data);
  }

  // Варианты шаблонов для этого объекта.
  $__options = array(
    "themes/{$theme}/templates/{$type}.{$name}.tpl",
    "themes/{$theme}/templates/{$type}.{$name}.php",
    "themes/{$theme}/templates/{$type}.default.tpl",
    "themes/{$theme}/templates/{$type}.default.php",
    "themes/all/templates/{$type}.{$name}.tpl",
    "themes/all/templates/{$type}.{$name}.php",
    "themes/all/templates/{$type}.default.tpl",
    "themes/all/templates/{$type}.default.php",
    );

  foreach ($__options as $__filename) {
    if (file_exists($__fullpath = $__root .'/'. $__filename)) {
      $data['prefix'] = '/'. dirname(dirname($__filename));

      ob_start();

      if (substr($__filename, -4) == '.tpl') {
        if (class_exists('BebopSmarty')) {
          $__smarty = new BebopSmarty($type == 'page');
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

      elseif (substr($__filename, -4) == '.php') {
        extract($data, EXTR_SKIP);
        include($__fullpath);
      }

      $output = ob_get_clean();
      return trim($output);
    }
  }
}

// Определяет тип файла.
function bebop_get_file_type($filename, $realname = null)
{
  $result = 'application/octet-stream';

  if (function_exists('mime_content_type')) {
    $result = mime_content_type($filename);
  }

  elseif (function_exists('finfo_open')) {
    if (false !== ($r = finfo_open(FILEINFO_MIME))) {
      $result = finfo_file($r, $filename);
      $result = str_replace(strrchr($result, ';'), '', $result);
      finfo_close($r);
    }
  }

  if (isset($realname) and ('application/octet-stream' == $result)) {
    switch (strrchr($realname, '.')) {
    case '.ttf':
      $result = 'application/x-font-ttf';
      break;
    }
  }

  return $result;
}

function mcms_fetch_file($url, $content = true, $cache = true)
{
  $outfile = mcms::config('tmpdir') . "/mcms-fetch.". md5($url);

  // Проверяем, не вышло ли время хранения файла на диске, если истекло - удаляем файл.
  // Если время жизни кэша не определено в конфигурации, принимаем его за астрономический один час.
  if (null === ($ttl = mcms::config('file_cache_ttl')))
    $ttl = 60 * 60;

  if (file_exists($outfile) and (!$cache or ((time() - $ttl) > @filectime($outfile))))
    if (is_writable(dirname($outfile)))
      unlink($outfile);

  // Скачиваем файл только если его нет на диске во временной директории
  if (!file_exists($outfile)) {
    $ch = curl_init($url);
    $fp = fopen($outfile, "w+");

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Molinos.CMS/' . mcms::version() . '; http://' . mcms::config('basedomain') . '/');

    if (!ini_get('safe_mode'))
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    fclose($fp);

    if (200 != $code) {
      unlink($outfile);
      return null;
    }
  }

  if ($content) {
    $content = file_get_contents($outfile);
    return $content;
  } else {
    return $outfile;
  }
}

function mcms_ctlname($name)
{
  if (substr($name, 0, 4) == 'Type')
    return substr($name, 4) .'Control';
  return $name;
}

function mcms_plain($text, $strip = true)
{
  if ($strip)
    $text = strip_tags($text);
  return str_replace(array('&amp;quot;'), array('&quot;'), htmlspecialchars($text, ENT_QUOTES));
}

function mcms_cut($text, $length)
{
  if (mb_strlen($text) > $length)
    $text = mb_substr(trim($text), 0, $length) .'...';
  return $text;
}

function mcms_url(array $options = null)
{
  $url = array_merge(bebop_split_url(), $options);
  return bebop_combine_url($url, false);
}

function mcms_encrypt($input)
{
    $textkey = mcms::config('guid');
    $securekey = hash('sha256', $textkey, true);

    $iv = mcrypt_create_iv(32);

    return rawurlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $securekey, $input, MCRYPT_MODE_ECB, $iv)));
}

function mcms_decrypt($input)
{
    $textkey = mcms::config('guid');
    $securekey = hash('sha256', $textkey, true);

    $iv = mcrypt_create_iv(32);

    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $securekey, base64_decode(rawurldecode($input)), MCRYPT_MODE_ECB, $iv));
}

class mcms
{
  const MEDIA_AUDIO = 1;
  const MEDIA_VIDEO = 2;
  const MEDIA_IMAGE = 4;

  const FLUSH_NOW = 1;

  public static function html($name, array $parts = null, $content = null)
  {
    $output = '<'. $name;

    if (('td' == $name or 'th' == $name) and empty($content))
      $content = '&nbsp;';

    // Прозрачная поддержка чистых урлов.
    foreach (array('img' => 'src', 'a' => 'href', 'form' => 'action') as $k => $v) {
      if ($k == $name and array_key_exists($v, $parts)) {
        if ('/' != substr($parts[$v], 0, 1) or !is_readable(substr($parts[$v], 1)))
          $parts[$v] = l($parts[$v]);
      }
    }

    if (null !== $parts) {
      foreach ($parts as $k => $v) {
        if (!empty($v)) {
          if (is_array($v))
            if ($k == 'class')
              $v = join(' ', $v);
            else {
              // bebop_debug("Trying to assign this to <{$name} {$k}= />", $v, $parts, $content);
              // throw new InvalidArgumentException(t("Свойство {$k} элемента HTML {$name} не может быть массивом."));
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

  public static function mediaGetPlayer(array $files, $types = null, array $custom_options = array())
  {
    $nodes = array();
    $havetypes = 0;

    if (null === $types)
      $types = self::MEDIA_AUDIO | self::MEDIA_VIDEO;

    foreach ($files as $k => $v) {
      switch ($v['filetype']) {
      case 'audio/mpeg':
        if ($types & self::MEDIA_AUDIO) {
          $nodes[] = $v['id'];
          $havetypes |= self::MEDIA_AUDIO;
        }
        break;
      case 'video/flv':
      case 'video/x-flv':
        if ($types & self::MEDIA_VIDEO) {
          $nodes[] = $v['id'];
          $havetypes |= self::MEDIA_VIDEO;
        }
        break;
      }
    }

    // Подходящих файлов нет, выходим.
    if (empty($nodes))
      return null;

    // Параметризация проигрывателя.
    $options = array_merge(array(
      'file' => 'http://'. $_SERVER['HTTP_HOST'] .'/playlist.rpc?nodes='. join(',', $nodes),
      'showdigits' => 'true',
      'autostart' => 'false',
      'repeat' => 'true',
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
    }

    $args = array();

    foreach ($options as $k => $v)
      $args[] = $k .'='. urlencode($v);

    $url = 'http://'. $_SERVER['HTTP_HOST'] .'/themes/all/flash/player.swf?'. join('&', $args);

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

  public static function config($key)
  {
    if (!class_exists('BebopConfig'))
      self::fatal('Отсутствует поддержка конфигурационных файлов.');

    return BebopConfig::getInstance()->$key;
  }

  public static function modconf($modulename, $key = null)
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
      return empty($cache[$modulename][$key]) ? null : $cache[$modulename][$key];
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

  public static function log($op, $message, $nid = null)
  {
    if (mcms::ismodule('syslog'))
      SysLogModule::log($op, $message, $nid);
  }

  public static function message($text = null)
  {
    $rc = null;

    $session =& mcms::user()->session;

    if (null === $text) {
      $rc = !empty($session['messages']) ? array_unique((array)$session['messages']) : null;
      $session['messages'] = array();
    } else {
      $msg = $session->messages;
      $msg[] = $text;
      $session->messages = $msg;
    }

    return $rc;
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

    $body .= t('<p>Here is the stack trace:</p><blockquote>%stack</blockquote>', array(
      '%stack' => str_replace("\n", '<br/>', $e->getTraceAsString()),
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
    if (mcms::user()->id != 0)
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
    $filename = 'tmp/.modmap.php';

    if (!is_dir($dir = dirname($filename))) {
      if (!mkdir($dir))
        throw new RuntimeException(t('Не удалось создать временный каталог.'));
      else
        chmod($dir, 0750);
    }

    if (file_exists($filename) and (filemtime($filename) < filemtime('lib/modules')))
      unlink($filename);

    if (!bebop_is_debugger() or empty($_GET['reload'])) {
      if (file_exists($filename) and is_readable($filename) and filesize($filename)) {
        if (is_array($result = unserialize(file_get_contents($filename))))
          return $result;
      }
    }

    $result = self::getModuleMapScan();

    if (null !== $name)
      return $result['modules'][$name];

    if (is_writable(dirname($filename)))
      file_put_contents($filename, serialize($result));

    if (!empty($_GET['reload'])) {
      $url = bebop_split_url();
      $url['args']['reload'] = null;
      $url['args']['FLUSH'] = 1;
      bebop_redirect(str_replace('FLUSH', 'flush', bebop_combine_url($url, false)));
    }

    return $result;
  }

  private static function getModuleMapScan()
  {
    static $lock = false;

    if ($lock)
      mcms::fatal(t('Повторный вход в getModuleMapScan().'));

    $lock = true;

    $root = dirname(__FILE__) .'/modules/';

    $enabled = explode(',', mcms::config('runtime_modules'));

    $result = array(
      'modules' => array(),
      'classes' => array(),
      'interfaces' => array(),
      );

    foreach ($modules = glob($root .'*') as $path) {
      $modname = basename($path);

      $result['modules'][$modname] = array(
        'classes' => array(),
        'interfaces' => array(),
        'implementors' => array(),
        'enabled' => $modok = in_array($modname, $enabled),
        );

      if (file_exists($modinfo = $path .'/module.info')) {
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
      foreach (glob($path .'/'. '*.*.php') as $classpath) {
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
            if (preg_match('@^\s*(abstract\s+){0,1}class\s+([^\s]+)(\s+extends\s+([^\s]+))*(\s+implements\s+(.+))*@im', file_get_contents($classpath), $m)) {
              $classname = $m[2];

              $result['modules'][$modname]['classes'][] = $classname;

              if (!empty($m[6]))
                $interfaces = explode(',', str_replace(' ', '', $m[6]));
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
              bebop_debug(time(), $classname, $classpath, $m, $result);
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

  public static function class_exists($name)
  {
    if (array_key_exists(strtolower($name), self::getClassMap()))
      return true;
    if (in_array($name, get_declared_classes()))
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

    print join(";\n\n", $output) .";\n\n";

    if (!empty($_SERVER['REMOTE_ADDR'])) {
      print "--- backtrace ---\n";
      print mcms::backtrace();
    }

    die();
  }

  public static function mail($from = null, $to, $subject, $text)
  {
    return MsgModule::send($from, $to, $subject, $text);
  }

  public static function version()
  {
    static $version = null;

    if (null === $version) {
      if (is_readable($fname = 'lib/version.info'))
        $version = file_get_contents($fname);
      else
        $version = 'trunk';
    }

    return $version;
  }

  public static function backtrace(array $stack = null)
  {
    $output = '';

    if (null === $stack)
      $stack = debug_backtrace();

    foreach ($stack as $k => $v) {
      if ($k > 0) {
        if (!empty($v['class']))
          $func = $v['class'] .$v['type']. $v['function'];
        else
          $func = $v['function'];

        $output .= sprintf("%2d. %s()", $k, $func);

        if (!empty($v['file']) and !empty($v['line']))
          $output .= sprintf(' — %s(%d)', str_replace($_SERVER['DOCUMENT_ROOT'] .'/', '', $v['file']), $v['line']);
        else
          $output .= ' — ???';

        $output .= "\n";
      }
    }

    return $output;
  }

  public static function eh(Exception $e)
  {
    if (ob_get_length())
      ob_end_clean();

    header('Content-Type: text/plain; charset=utf-8');

    printf("%s: %s\n", get_class($e), $e->getMessage());

    if ($e instanceof UserErrorException)
      printf("Description: %s\n", $e->getNote());

    if ($e instanceof TableNotFoundException) {
      if (null !== ($tmp = $e->getQuery()))
        printf("\nSQL:    %s\n", $tmp);
      if (null !== ($tmp = $e->getParams()))
        printf("Params: %s\n", preg_replace('/\s*[\n\r]+\s*/', ' ', var_export($tmp, true)));
    }

    printf("\nLocation: %s(%d)\n", str_replace($_SERVER['DOCUMENT_ROOT'] .'/', '', $e->getFile()), $e->getLine());

    print $message;

    print "\n--- backtrace ---\n";
    print mcms::backtrace($e->getTrace());

    exit();
  }
};

set_exception_handler('mcms::eh');
