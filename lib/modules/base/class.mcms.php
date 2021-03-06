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
  const FLUSH_NOW = 1;

  const VERSION_CURRENT = 1;
  const VERSION_RELEASE = 2;
  const VERSION_STABLE = 2;

  public static function config($key, $default = null)
  {
    return Context::last()->config->get($key, $default);
  }

  // Отладочные функции.
  public static function debug()
  {
    if (empty($_SERVER['HTTP_HOST']) or ($ctx = Context::last()) and $ctx->canDebug()) {
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
            $output[] = preg_replace('/ =>\s+/', ' => ', self::dump($arg))
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
        print Logger::backtrace();

        if (function_exists('memory_get_peak_usage'))
          printf("\n--- memory usage ---\nnow:  %s\npeak: %s\n",
            self::filesize(memory_get_usage()),
            self::filesize(memory_get_peak_usage()));
      }

      die();
    }
  }

  private static function dump($value)
  {
    ob_start();
    var_dump($value);
    return trim(ob_get_clean());
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

  public static function now($time = null)
  {
    if (null === $time)
      $time = time();
    return date('Y-m-d H:i:s', $time - date('Z', $time));
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
        $ctx = Context::last();

      $result['at'] = $ctx->host() . $ctx->folder();
      $result['version_link'] = 'http://code.google.com/p/molinos-cms/wiki/ChangeLog_' . str_replace('.', '_', mcms::version(mcms::VERSION_STABLE));

      if ($full) {
        $options = array();

        if (count($parts = explode(':', mcms::config('db'))))
          if (in_array($parts[0], Database::listDrivers()))
            $options[] = $parts[0];

        $options[] = str_replace('_provider', '', get_class(cache::getInstance()));
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
