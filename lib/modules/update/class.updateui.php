<?php

class UpdateUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
    $header = html::em('h1', t('Проверка обновлений'));
    $message = html::em('p', t('Обновлений нет, '
      .'вы используете самую свежую версию CMS.'));

    if (file_exists($tmp = mcms::config('tmpdir') .'/update.txt')) {
      list($version, $filename) = explode(',', trim(file_get_contents($tmp)));

      if (file_exists($filename)) {
        if (version_compare($version, mcms::version()) == 1) {
          $message = t('Вы используете устаревшую версию Molinos.CMS '
            .'(%current, в то время как уже вышла '
            .'<a href=\'@url\'>%available</a>); пожалуйста, обновитесь.', array(
              '%current' => mcms::version(),
              '%available' => $version,
              '@url' => 'http://code.google.com/p/molinos-cms/wiki/ChangeLog_'.
                str_replace('.', '', mcms::version(mcms::VERSION_RELEASE)),
              ));

          $input = html::em('input', array(
            'type' => 'submit',
            'value' => 'Скачать и установить',
            ));
          $form = html::em('form', array(
            'method' => 'post',
            'action' => '?q=update.rpc&action=update',
            ), $input);

          $message .= $form;
        }
      }
    }

    return $header . $message;
  }
}
