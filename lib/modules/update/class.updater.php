<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Updater implements iAdminUI, iRemoteCall
{
  public static function onGet(RequestContext $ctx)
  {
    $version = mcms::version();
    $available = mcms::version(mcms::VERSION_AVAILABLE);

    $form = null;

    switch ($res = version_compare($available, $version)) {
      case -1:
        $message = t('Вы используете версию, которая ещё не была выпущена в свет. Для вас нет обновлений, зато есть возможность <a href=\'@url\'>поучаствовать в развитии системы</a>.', array('@url' => 'http://code.google.com/p/molinos-cms/issues/list?q=label:Milestone-R'. mcms::version(mcms::VERSION_RELEASE)));
        break;
      case 0:
        $message = t('Вы используете самую свежую версию Molinos.CMS.');
        break;
      case 1:
        $message = t('Вы используете устаревшую версию Molinos.CMS (%current, в то время как уже вышла <a href=\'@url\'>%available</a>); пожалуйста, обновитесь.', array(
          '%current' => $version,
          '%available' => $available,
          '@url' => 'http://code.google.com/p/molinos-cms/wiki/ChangeLog_'. str_replace('.', '', mcms::version(mcms::VERSION_RELEASE)),
          ));

        $input = mcms::html('input', array(
          'type' => 'submit',
          'value' => 'Скачать и установить',
          ));
        $form = mcms::html('form', array(
          'method' => 'post',
          'action' => 'update.rpc?action=update',
          ), $input);
        break;
      default:
        throw new RuntimeException('Неопознанное значение version_compare().');
    }

    $output = mcms::html('h1', t('Проверка обновлений'));
    $output .= mcms::html('p', $message);

    if (null !== $form)
      $output .= $form;

    return $output;
  }

  public static function hookRemoteCall(RequestContext $ctx)
  {
    switch ($ctx->get('action')) {
    case 'update':
      self::download();

    default:
      throw new BadRequestException();
    }
  }

  private static function download()
  {
    $url = mcms::version(mcms::VERSION_AVAILABLE_URL);

    // $tmpname = mcms_fetch_file($url, false);
    // FIXME: почему-то mcms_fetch_file скачивает не полный файл.
    $tmpname = self::download_raw($url);

    if (null === $tmpname or !is_readable($tmpname))
      throw new RuntimeException(t('Не удалось скачать свежий инсталляционный пакет Molinos.CMS.'));

    self::unpack($tmpname);

    mcms::redirect('admin');
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

        // Создаём новый.
        if (false === ($out = fopen($path, "wb"))) {
          // Не удалось -- возвращаем старый на место.
          rename($path .'.old', $path);
          throw new InvalidArgumentException(t("Не удалось распаковать файл %path", array('%path' => $path)));
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
  }
}
