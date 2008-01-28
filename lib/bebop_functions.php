<?php

function bebop_redirect($path, $status = 301)
{
    if (is_array($path))
      $path = bebop_combine_url($path, false);

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
      $status = 303;

    if (!in_array($status, array('301', '302', '303', '307')))
      throw new Exception("Статус перенаправления {$status} не определён в стандарте HTTP/1.1");

    bebop_session_end();
    PDO_Singleton::getInstance()->commit();
    BebopCache::getInstance()->flush(true);

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

function bebop_mail($from, $to, $subject, $body, array $attachments = null)
{
  require_once(dirname(__FILE__) .'/modules/mimemail/mimemail.php');
  return BebopMimeMail::send($from, $to, $subject, $body, $attachments);
}

// Возвращает массив интерфейсов и реализующих их классов.
function bebop_get_interface_map($name = null)
{
  $map = array();

  foreach (bebop_get_module_map() as $module) {
    if (!empty($module['interface']))
      foreach ($module['interface'] as $k => $v) {
        if (empty($map[$k]))
          $map[$k] = $v;
        else
          $map[$k] = array_merge($map[$k], $v);
      }
  }

  if ($name !== null)
    return empty($map[$name]) ? array() : $map[$name];

  return $map;
}

// Проверяет, является ли пользователь отладчиком.
function bebop_is_debugger()
{
  static $skip = false;

  if ($skip === false) {
    if (empty($_SESSION)) {
      bebop_session_start();
      bebop_session_end();
    }

    if (empty($_SERVER['REQUEST_METHOD'])) {
      $skip = false;
    }

    elseif (!empty($_SESSION['user']['systemgroups']) and in_array('Developers', $_SESSION['user']['systemgroups']))
      $skip = false;

    else {
      $config = BebopConfig::getInstance();
      if (empty($config->debuggers))
        $skip = true;
      elseif (!in_array($_SERVER['REMOTE_ADDR'], $list = preg_split('/[, ]+/', $config->debuggers)))
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
    $output = array();

    foreach (func_get_args() as $arg) {
      $output[] = var_export($arg, true);
    }

    bebop_on_json(array('args' => $output));

    ob_end_clean();

    if (!empty($_SERVER['REQUEST_METHOD']))
      header("Content-Type: text/plain; charset=utf-8");

    print join(";\n\n", $output) .";\n\n";

    if (!empty($_SERVER['REMOTE_ADDR'])) {
      print "--- backtrace ---\n";
      debug_print_backtrace();
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

  $forbidden = array('nocache', 'flush');

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
function l($title, array $args, array $options = null)
{
  $url = bebop_split_url();
  $url['args'] = array_merge($url['args'], $args);

  foreach (array('flush', 'nocache') as $k)
    if (array_key_exists($k, $url['args']))
      unset($url['args'][$k]);

  $mod = '';

  if (!empty($options['class']))
    $mod .= " class='{$options['class']}'";
  if (!empty($options['title']))
    $mod .= " title='". mcms_plain($options['title']) ."'";
  if (!empty($options['id']))
    $mod .= " id='{$options['id']}'";

  if ($title === null)
    return bebop_combine_url($url, false);
  else
    return "<a href='". bebop_combine_url($url, true) ."'{$mod}>{$title}</a>";
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
    $sth = PDO_Singleton::getInstance()->prepare("SELECT m2.* FROM `node__messages` m1 LEFT JOIN `node__messages` m2 ON m1.id = m2.id WHERE m2.lang = :lang AND m1.message = :message");

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
      $message = str_replace($k, mcms_plain($v), $message);
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
    PDO_Singleton::getInstance()->commit();

    setlocale(LC_ALL, "en_US.UTF-8");

    $output = json_encode($result);
    header('Content-Type: application/x-json');
    header('Content-Length: '. strlen($output));
    die($output);
  }
}

// Применяет шаблон к данным.
function bebop_render_object($type, $name, $theme, $data)
{
  $__root = $_SERVER['DOCUMENT_ROOT'];

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
function bebop_get_file_type($filename)
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

  return $result;
}

static $bebop_session_status = false;

function bebop_session_start($check = false)
{
  global $bebop_session_status;

  if (!$check and !$bebop_session_status) {
    session_start();
    $bebop_session_status = true;
  }

  return $bebop_session_status;
}

function bebop_session_end()
{
  global $bebop_session_status;

  if ($bebop_session_status) {
    session_write_close();
    $bebop_session_status = false;
  }
}

function mcms_fetch_file($url, $content = true, $cache = true)
{
  $outfile = BebopConfig::getInstance()->tmpdir . "/mcms-fetch.". md5($url);

  // Проверяем, не вышло ли время хранения файла на диске, если истекло - удаляем файл.
  // Если время жизни кэша не определено в конфигурации, принимаем его за астрономический один час.
  if (null === ($ttl = BebopConfig::getInstance()->file_cache_ttl))
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Molinos.CMS/' . BEBOP_VERSION . '; http://' . BebopConfig::getInstance()->basedomain . '/');

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

function mcms_log($action, $nid = null, $query = null)
{
  static $sth = null;

  if ($sth === null)
    $sth = PDO_Singleton::getInstance()->prepare("INSERT INTO `node__log` (`nid`, `uid`, `ip`, `operation`, `timestamp`, `username`, `query`) VALUES (:nid, :uid, :ip, :operation, NOW(), :username, :query)");

  $user = AuthCore::getInstance()->getUser();

  $sth->execute(array(
      ':nid' => $nid,
      ':uid' => $user->getUid(),
      ':username' => $user->getUid() ? $user->getName() : 'anonymous',
      ':ip' => empty($_SERVER['REMOTE_ADDR']) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'],
      ':operation' => $action,
      ':query' => empty($query) ? null : $query,
      ));
}

function mcms_url(array $options = null)
{
  $url = array_merge(bebop_split_url(), $options);
  return bebop_combine_url($url, false);
}

function mcms_encrypt($input)
{
    $textkey = BebopConfig::getInstance()->guid;
    $securekey = hash('sha256', $textkey, true);

    $iv = mcrypt_create_iv(32);

    return rawurlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $securekey, $input, MCRYPT_MODE_ECB, $iv)));
}

function mcms_decrypt($input)
{
    $textkey = BebopConfig::getInstance()->guid;
    $securekey = hash('sha256', $textkey, true);

    $iv = mcrypt_create_iv(32);

    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $securekey, base64_decode(rawurldecode($input)), MCRYPT_MODE_ECB, $iv));
}

class mcms
{
  const MEDIA_AUDIO = 1;
  const MEDIA_VIDEO = 2;
  const MEDIA_IMAGE = 4;

  public static function html($name, array $parts = null, $content = null)
  {
    $output = '<'. $name;

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

    if (null === $content and $name != 'a' and $name != 'script' and $name != 'div' and $name != 'textarea') {
      $output .= ' />';
    } else {
      $output .= '>'. $content .'</'. $name .'>';
    }

    return $output;
  }

  public static function mediaGetPlayer(array $files, $types = null, array $custom_options = array())
  {
    $nodes = array();

    if (null === $types)
      $types = self::MEDIA_AUDIO | self::MEDIA_VIDEO;

    foreach ($files as $k => $v) {
      switch ($v['filetype']) {
      case 'audio/mpeg':
        if ($types & self::MEDIA_AUDIO)
          $nodes[] = $v['id'];
        break;
      case 'video/x-flv':
        if ($types & self::MEDIA_VIDEO)
          $nodes[] = $v['id'];
        break;
      }
    }

    // Подходящих файлов нет, выходим.
    if (empty($nodes))
      return null;

    // Параметризация проигрывателя.
    $options = array_merge(array(
      'file' => 'http://'. $_SERVER['HTTP_HOST'] .'/playlist/'. join(',', $nodes) .'.xspf',
      'showdigits' => 'true',
      'autostart' => 'false',
      'repeat' => 'true',
      'shuffle' => 'false',
      'width' => 350,
      'height' => 100,
      'showdownload' => 'false',
      'displayheight' => 0,
      ), $custom_options);

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

  public static function mediaGetPlaylist(array $nids)
  {
    $output = '';
    $tracks = array();

    foreach ($nodes = Node::find(array('class' => 'file', 'id' => $nids)) as $node) {
      $track = mcms::html('title', array(), $node->name);
      $track .= mcms::html('location', array(), 'http://'. $_SERVER['HTTP_HOST'] .'/attachment/'. $node->id .'?'. $node->filename);
      $tracks[] = mcms::html('track', array(), $track);
    }

    if (empty($tracks))
      throw new PageNotFoundException();

    header('Content-Type: application/xspf+xml; charset=utf-8');

    // TODO: если запрошен один документ, и это — не файл, можно сразу возвращать все его файлы.

    $output .= "<?xml version='1.0' encoding='utf-8'?>";
    $output .= "<playlist version='1' xmlns='http://xspf.org/ns/0/'>";
    $output .= mcms::html('trackList', array(), join('', $tracks));
    $output .= '</playlist>';

    return $output;
  }
};
