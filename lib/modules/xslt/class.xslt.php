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

    if (false === ($output = mcms::cache($ckey = 'xml:xsl:' . md5($xml) . ',' . filemtime($xsltName))) or $nocache) {
      $doc = new DOMDocument;
      $doc->loadXML($xml);
      self::checkErrors();

      if (class_exists('xsltCache') and !$nocache) {
        $proc = new xsltCache;
        $proc->importStyleSheet($xsltName);
      } else {
        $xsl = new DOMDocument;
        $xsl->load($xsltName);
        self::checkErrors($xsltName);

        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
      }

      self::checkErrors();

      mcms::cache($ckey, $output = $proc->transformToXML($doc));
    }

    if (null === $mimeType)
      return trim(str_replace('<?xml version="1.0"?>', '', $output));

    return new Response($output, $mimeType);
  }

  private static function checkErrors($fileName = null)
  {
    if (null !== ($e = error_get_last())) {
      if (__FILE__ == $e['file']) {
        if (null !== $fileName)
          $e['message'] .= ', file: ' . $fileName;
        throw new RuntimeException(t('XSLT шаблон содержит ошибки: %message.', array(
          '%message' => $e['message'],
          )));
      }
    }
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
}
