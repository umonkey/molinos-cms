<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Проверяет, является ли пользователь отладчиком.
function bebop_is_debugger()
{
  static $skip = false;

  if ($skip === false) {
    if (empty($_SERVER['REQUEST_METHOD']))
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
  if ($url instanceof Node) {
    if (null === $title) {
      if ('user' == $url->class and !empty($url->fullname))
        $title = mcms_plain($url->fullname);
      else
        $title = mcms_plain($url->name);
    }
    return l('node/'. $url->id, $title, $options, $absolute);
  }

  elseif (is_array($url) and array_key_exists('class', $url)) {
    $node = Node::create($url['class'], $url);
    return l($node, $title, $options, $absolute);
  }

  elseif (is_numeric($url)) {
    $node = Node::load($url);
    return l($node, $title, $options, $absolute);
  }

  if (empty($url))
    throw new RuntimeException(t('Не указана ссылка для l().'));
  elseif (!is_string($url))
    throw new RuntimeException(t('Ссылка для l() должна быть строкой.'));

  $parts = new url($url);

  if ($parts->islocal)
    $url = strval($parts);

  if (false !== strpos($url, '=CURRENT'))
    $url = str_replace('CURRENT', urlencode($_SERVER['REQUEST_URI']), $url);

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
function bebop_render_object($type, $name, $theme = null, $data = array(), $classname = null)
{
  // Префикс всегда фиксированный и показывает на корень сайта.  Это нужно
  // для корректной подгрузки стилей и скриптов, а также для работы
  // относительных ссылок в любой ветке сайта.  Привязка к типовым страницам
  // намеренно отключена, чтобы ссылка "node/123" _всегда_ вела в одно место.
  $data['base'] = 'http://'. $_SERVER['HTTP_HOST'] . mcms::path() .'/';

  $__options = bebop_get_templates($type, $name, $theme, $classname);

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

  // Передаём шаблону путь к шкуре.
  $data['theme'] = $theme;

  // Исправляем разделители пути (для Windows итд).
  foreach ($__options as $k => $v)
    $__options[$k] = str_replace('/', DIRECTORY_SEPARATOR, $v);

  if (!mcms::ismodule('smarty')) {
    foreach ($__options as $k => $v) {
      if (substr($v, -4) == '.tpl' and file_exists($v)) {
        throw new RuntimeException(t('Вы попытались использовать шаблон на '
          .'Smarty, однако соответствующий модуль отключен.  Попросите '
          .'администратора сайта его включить.'));
      }
    }
  }

  foreach ($__options as $__filename) {
    if (false !== ($output = mcms::render($__filename, $data)))
      return $output;
  }

  return false;
}

function bebop_get_templates($type, $name, $theme = null, $classname = null)
{
  if (null === $theme)
    $theme = 'all';
  // Сокращённое указание темы, дополняем.
  elseif (false === strpos($theme, '/'))
    $theme = 'themes/'. $theme .'/templates';

  // Варианты шаблонов для этого объекта.
  if (null !== $type and null !== $name) {
    $__options = array(
      "{$theme}/{$type}.{$name}.tpl",
      "{$theme}/{$type}.{$name}.php",
      "{$theme}/{$type}.{$name}.phtml",
      "{$theme}/{$type}.default.tpl",
      "{$theme}/{$type}.default.php",
      "{$theme}/{$type}.default.phtml",
      );
  } else {
    $__options = array();
  }

  // Если класс существует — добавляем его дефолтный шаблон в конец.
  if (null !== $classname) {
    $key = strtolower($classname);
    if (array_key_exists($key, $classmap = mcms::getClassMap())) {
      $rp = str_replace('.php', '.phtml', $classmap[$key]);
      if (is_readable($rp))
        $__options[] = $rp;
    }
  }

  return $__options;
}

// Определяет тип файла.
function bebop_get_file_type($filename, $realname = null)
{
  if (strrchr($filename, '.')) {
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
    case '.mp3':
      return 'audio/mpeg';
    }
  }

  if (false !== strpos($filename, 'archive.org/stream/'))
    return 'audio/x-mpegurl';

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
  if (file_exists($outfile)) {
    mcms::log('fetch', 'found in cache: '. $url);
  } else {
    if (function_exists('curl_init')) {
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

      mcms::log('fetch', 'read with curl: '. $url);
    }

    elseif ($f = fopen($url, 'rb')) {
      if (!($out = fopen($outfile, 'w')))
        throw new RuntimeException(t('Не удалось сохранить временный файл %name', array('%name' => $outfile)));

      while (!feof($f))
        fwrite($out, fread($f, 1024));

      fclose($f);
      fclose($out);

      mcms::log('fetch', 'read with fopen: '. $url);
    }

    else {
      throw new RuntimeException(t('Не удалось загрузить файл: '
        .'модуль CURL отсутствует, '
        .'открыть поток HTTP тоже не удалось.'));
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
