<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Updater implements iAdminUI, iRemoteCall
{
  public static function onGet(Context $ctx)
  {
    $header = mcms::html('h1', t('Проверка обновлений'));
    $message = mcms::html('p', t('Обновлений нет, '
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

          $input = mcms::html('input', array(
            'type' => 'submit',
            'value' => 'Скачать и установить',
            ));
          $form = mcms::html('form', array(
            'method' => 'post',
            'action' => '?q=update.rpc&action=update',
            ), $input);

          $message .= $form;
        }
      }
    }

    return $header . $message;
  }

  public static function hookRemoteCall(Context $ctx)
  {
    switch ($ctx->get('action')) {
    case 'update':
      self::download($ctx);

    case 'rebuild':
      Loader::rebuild();
      $ctx->redirect('admin');

    default:
      throw new BadRequestException();
    }
  }

  private static function download(Context $ctx)
  {
    $url = mcms::version(mcms::VERSION_AVAILABLE_URL);

    // $tmpname = mcms_fetch_file($url, false);
    // FIXME: почему-то mcms_fetch_file скачивает не полный файл.
    $tmpname = self::download_raw($url);

    if (null === $tmpname or !is_readable($tmpname))
      throw new RuntimeException(t('Не удалось скачать свежий инсталляционный пакет Molinos.CMS.'));

    self::unpack($tmpname);

    $ctx->redirect('?q=update.rpc&action=rebuild');
  }

  private static function download_raw($url)
  {
    if (false === ($src = fopen($url, 'rb')))
      throw new RuntimeException(t("Не удалось скачать файл %url.", array('%url' => $info['download_url'])));

    if (false === ($dst = fopen($zipname = 'tmp/bebop-update.zip', 'wb')))
      throw new RuntimeException(t("Не удалось сохранить дистрибутив в %path.", array('%path' => $zipname)));

    while (!feof($src))
      fwrite($dst, fread($src, 8192));

    fclose($src);
    fclose($dst);

    return $zipname;
  }

  private static function unpack($zipname)
  {
    $f = zip_open($zipname);

    $bootstrap = null;

    while ($entry = zip_read($f)) {
      $path = zip_entry_name($entry);

      // Каталог.
      if (substr($path, -1) == '/') {
        if (!is_dir($path))
          mkdir($path);
      }

      // Обычный файл.
      else {
        // Удаляем существующий.
        if (file_exists($path))
          rename($path, $path .'.old');

        // Этот файл переписываем в самом конце.
        if (basename($new = $path) == 'bootstrap.php')
          $bootstrap = $new = $path .'.new';

        // Создаём новый.
        if (false === ($out = fopen($new, "wb"))) {
          // Не удалось -- возвращаем старый на место.
          rename($path .'.old', $path);
          throw new RuntimeException(t("Не удалось распаковать файл %path", array('%path' => $path)));
        }

        // Размер нового файла.
        $size = zip_entry_filesize($entry);

        // Разворачиваем файл.
        fwrite($out, zip_entry_read($entry, $size), $size);

        // Закрываем.
        fclose($out);

        // Проставим нормальные права.
        chmod($path, 0664);

        // Удалим старую копию.
        if (file_exists($path .'.old'))
          unlink($path .'.old');
      }
    }

    zip_close($f);

    // Архив успешно распакован, можно обновить загрузчик.
    if (null !== $bootstrap)
      rename($bootstrap, substr($bootstrap, 0, -4));
    else
      throw new RuntimeException(t('Не удалось распаковать загрузчик.'));
  }
}
