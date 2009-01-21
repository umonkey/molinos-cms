<?php

class xslt
{
  public static function transform($xml, $xsltName)
  {
    if (!file_exists($xsltName))
      throw new RuntimeException(t('Шаблон %name не найден.', array(
        '%name' => $xsltName,
        )));

    if (!empty($_GET['xslt'])) {
      switch ($_GET['xslt']) {
      case 'client':
        $xml = str_replace('?>',
          '?><?xml-stylesheet type="text/xsl" href="' . $xsltName . '"?>',
          $xml);
        break;
      case 'none':
        break;
      }

      return new Response($xml, 'text/xml');
    }

    $doc = new DOMDocument;
    $doc->loadXML($xml);
    self::checkErrors();

    if (class_exists('xsltCache') and false) {
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

    $output = $proc->transformToXML($doc);

    return new Response($output, 'text/html');
  }

  private static function checkErrors($fileName = null)
  {
    if (null !== ($e = error_get_last())) {
      if (__FILE__ == $e['file']) {
        if (null !== $fileName)
          $e['message'] .= ', file: ' . $fileName;
        throw new RuntimeException($e['message']);
      }
    }
  }
}
