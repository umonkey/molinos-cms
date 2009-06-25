<?php

class xslt
{
  private static $lock = null;

  public static function transform($xml, $xsltName, $mimeType = 'text/html', $status = 200)
  {
    if (null !== self::$lock) {
      Logger::backtrace('XSLT recursion: ' . $xsltName . ' while in ' . self::$lock);
      throw new RuntimeException(t('Рекурсия в XSLT недопустима.'));
    }

    $mode = empty($_GET['xslt'])
      ? 'server'
      : $_GET['xslt'];

    $xml = self::fixEntities($xml);

    if ('none' == $mode or empty($xsltName))
      return new Response('<?xml version="1.0"?>' . $xml, 'text/xml', $status);

    if (!file_exists($xsltName))
      throw new RuntimeException(t('Шаблон %name не найден.', array(
        '%name' => $xsltName,
        )));

    if ('client' == $mode) {
      $xml = str_replace('?>',
        '?><?xml-stylesheet type="text/xsl" href="' . $xsltName . '"?>',
        $xml);
      return new Response($xml, 'text/xml');
    }

    $nocache = !empty($_GET['nocache']);

    $cache = cache::getInstance();
    $ckey = 'xml:xsl:' . md5($xml) . ',' . filemtime($xsltName);

    if (false === ($output = $cache->$ckey) or $nocache) {
      set_error_handler(array(__CLASS__, 'eh'));

      $doc = new DOMDocument;
      $doc->loadXML($xml);

      if (class_exists('xsltCache') and !$nocache) {
        $proc = new xsltCache;
        $proc->importStyleSheet($xsltName);
      } else {
        $xsl = new DOMDocument;
        @$xsl->load($xsltName);

        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
      }

      self::$lock = $xsltName;
      if ($output = str_replace(' xmlns=""', '', $proc->transformToXML($doc)))
        $cache->$ckey = $output;
      self::$lock = null;

      restore_error_handler();
    }

    if (empty($output))
      throw new RuntimeException(t('Шаблон %xslt ничего не вернул.', array(
        '%xslt' => $xsltName
        )));

    if (null === $mimeType)
      return trim(str_replace('<?xml version="1.0"?>', '', $output));

    return new Response($output, $mimeType, $status);
  }

  public static function fixEntities($xml)
  {
    $map = array(
      '&hellip;' => '…',
      '&plusmn;' => '±',
      '&ge;' => '≥',
      '&le;' => '≤',
      '&ne;' => '≠',
      '&equiv;' => '≡',
      );
    return str_replace(array_keys($map), array_values($map), $xml);
  }

  public static function eh($errno, $errstr, $errfile, $errline, $errcontext)
  {
    $message = str_replace("\n", '<br/>', $errstr);
    Logger::trace($message);
    throw new RuntimeException($message);
  }
}
