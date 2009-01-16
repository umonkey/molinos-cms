<?php

class PHTMLEngine implements iTemplateProcessor
{
  public static function getExtensions()
  {
    return array('phtml', 'php');
  }

  public static function processTemplate($fileName, array $data)
  {
    if (array_key_exists('prefix', $data))
      throw new RuntimeException(t('Параметр $prefix зарезервирован и не может быть передан шаблону.'));

    $data['prefix'] = dirname(dirname($fileName));

    if (array_key_exists('data', $data))
      extract($data, EXTR_OVERWRITE);
    else {
      extract($data, EXTR_OVERWRITE);
      unset($data);
    }

    ob_start();
    include $fileName;
    return ob_get_clean();
  }
}
