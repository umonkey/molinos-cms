<?php

class xslt
{
  public static function transform($xml, $xsltName, $mimeType = 'text/html')
  {
    $mode = empty($_GET['xslt'])
      ? 'server'
      : $_GET['xslt'];

    $xml = self::fixEntities($xml);

    if ('none' == $mode or empty($xsltName))
      return new Response('<?xml version="1.0"?>' . $xml, 'text/xml');

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
      @$doc->loadXML($xml);

      if (class_exists('xsltCache') and !$nocache) {
        $proc = new xsltCache;
        $proc->importStyleSheet($xsltName);
      } else {
        $xsl = new DOMDocument;
        @$xsl->load($xsltName);

        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
      }

      restore_error_handler();

      if ($output = $proc->transformToXML($doc))
        $cache->$ckey = $output;
    }

    if (empty($output))
      mcms::fatal(t('Шаблон %xslt ничего не вернул.', array(
        '%xslt' => $xsltName
        )));

    if (null === $mimeType)
      return trim(str_replace('<?xml version="1.0"?>', '', $output));

    return new Response($output, $mimeType);
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
    mcms::fatal(str_replace("\n", '<br/>', $errstr));
  }
}
