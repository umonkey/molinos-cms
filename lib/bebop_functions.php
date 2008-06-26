<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
        $skip = false;
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

// Разбивает текущий запрос на составляющие.
function bebop_split_url($url = null)
{
  mcms::deprecated();
  $tmp = new url($url);
  return $tmp->as_array();
}

// Заворачивает результат работы предыдущей функции обратно.
function bebop_combine_url(array $url, $escape = true)
{
  return strval(new url($url));
}

// Возвращает отформатированную ссылку.
function l($url, $title = null, array $options = null, $absolute = false)
{
  if (empty($url))
    throw new RuntimeException(t('Не указана ссылка для l().'));
  elseif (!is_string($url))
    throw new RuntimeException(t('Ссылка для l() должна быть строкой.'));

  $parts = bebop_split_url($url);

  if (empty($parts['host'])) {
    foreach (array('smarty.debug') as $k)
      if (array_key_exists($k, $parts['args']))
        unset($parts['args'][$k]);

    if ($absolute)
      $parts['#absolute'] = true;

    $url = bebop_combine_url($parts, false);
  }

  $options['href'] = $url;

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
function bebop_render_object($type, $name, $theme = null, $data, $classname = null)
{
  $__root = MCMS_ROOT;

  $data['base'] = "http://{$_SERVER['HTTP_HOST']}". rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') .'/';

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

  if (!mcms::ismodule('smarty')) {
    foreach ($__options as $k => $v) {
      if (substr($v, -4) == '.tpl' and file_exists($v)) {
        throw new RuntimeException(t('Вы попытались использовать шаблон на '
          .'Smarty, однако соответствующий модуль отключен.  Попросите '
          .'администратора сайта его включить.'));
      }
    }
  }

  // Если класс существует — добавляем его дефолтный шаблон в конец.
  if (array_key_exists($key = strtolower($classname), $classmap = mcms::getClassMap())) {
    $__options[] = ltrim(str_replace(MCMS_ROOT, '', str_replace('.php', '.phtml', $classmap[$key])), '/');
  }

  foreach ($__options as $__filename) {
    if (file_exists($__fullpath = $__root .'/'. $__filename)) {
      // $data['prefix'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') .'/'. dirname(dirname($__filename));
      $data['prefix'] = rtrim(dirname(dirname($__filename)), '/');

      ob_start();

      $ext = strrchr($__filename, '.');

      if ($ext == '.tpl') {
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

      elseif ($ext == '.php' or $ext == '.phtml') {
        extract($data, EXTR_SKIP);
        include($__fullpath);
      }

      $output = ob_get_clean();

      if (file_exists($tmp = str_replace($ext, '.css', $__filename)))
        mcms::extras($tmp);

      if (file_exists($tmp = str_replace($ext, '.js', $__filename)))
        mcms::extras($tmp);

      return trim($output);
    }
  }
}

// Определяет тип файла.
function bebop_get_file_type($filename, $realname = null)
{
  if (false !== strstr($filename, '.')) {
    switch (substr($filename, strrpos($filename, '.'))) {
    case '.pdf':
      return 'application/pdf';
    case '.desktop':
      return 'application/x-gnome-shortcut';
    case '.bmp':
      return 'image/bmp';
    case '.gif':
      return 'image/gif';
    case '.jpg':
    case '.jpeg':
      return 'image/jpeg';
    case '.png':
      return 'image/png';
    }
  }

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

  $ttl = mcms::config('file_cache_ttl', 3600);

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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Molinos.CMS/' . mcms::version() . '; ' . l('/'));

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

    if (function_exists('get_headers')) {
      $headers = get_headers($url, true);

      if (!empty($headers['Content-Length']) and ($real = $headers['Content-Length']) != ($got = filesize($outfile))) {
        unlink($outfile);
        throw new RuntimeException(t('Не удалось скачать файл: вместо %real байтов было получено %got.', array('%got' => $got, '%real' => $real)));
      }
    }
  }

  if ($content) {
    $content = file_get_contents($outfile);
    return $content;
  } else {
    return $outfile;
  }
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
