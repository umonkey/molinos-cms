<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
  if (empty($url['host']) and !empty($url['args']['__cleanurls']))
    $url['args']['q'] = null;

  $url = new url($url);
  $url = $url->string();

  return $escape ? htmlspecialchars($url) : $url;
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
    $url = $parts->string();

  if (false !== strpos($url, '=CURRENT'))
    $url = str_replace('CURRENT', urlencode($_SERVER['REQUEST_URI']), $url);

  $options['href'] = $url;

  if (null === $title)
    return $options['href'];

  return html::em('a', $options, $title);
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
      $message = str_replace($k, htmlspecialchars(l($v)), $message);
      break;
    }
  }

  return $message;
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
    if (null !== ($classpath = Loader::getClassPath($key))) {
      $rp = str_replace('.php', '.phtml', $classpath);
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
    switch (strtolower(substr($filename, strrpos($filename, '.')))) {
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
    case '.php':
    case '.txt':
      return 'text/plain';
    case '.zip':
      return 'application/zip';
    case '.flv':
      return 'video/flv';
    }
  }

  if (false !== strpos($filename, 'archive.org/stream/'))
    return 'audio/x-mpegurl';

  $result = 'application/octet-stream';

  if (function_exists('mime_content_type')) {
    $result = mime_content_type($filename);
  }

  elseif (function_exists('finfo_open')) {
    if (false !== ($r = @finfo_open(FILEINFO_MIME))) {
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
  if (function_exists('mcrypt_create_iv') and ($key = mcms::config('guid'))) {
    $securekey = hash('sha256', $key, true);

    if (!function_exists('mcrypt_create_iv'))
      throw new RuntimeException(t('Function mcrypt_create_iv not found.'));

    $iv = mcrypt_create_iv(32);

    $input = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $securekey, $input, MCRYPT_MODE_ECB, $iv);
  }

  return rawurlencode(base64_encode($input));
}

function mcms_decrypt($input)
{
  $input = base64_decode(rawurldecode($input));

  if (function_exists('mcrypt_create_iv') and ($key = mcms::config('guid'))) {
    $securekey = hash('sha256', $key, true);

    $iv = mcrypt_create_iv(32);

    $input = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $securekey, $input, MCRYPT_MODE_ECB, $iv);
  }

  return $input;
}
