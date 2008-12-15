<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Updater implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_update(Context $ctx)
  {
    modman::updateDB();
    Loader::rebuild();
  }

  public static function rpc_upgrade(Context $ctx)
  {
    $status = array();

    $db = modman::getAllModules();
    $modules = $ctx->post('modules', array());

    $url = new url($ctx->get('destination'));
    $url->setarg('status', null);

    foreach ($modules as $module) {
      $info = $db[$module];

      $key = modman::updateModule($module)
        ? 'updated'
        : 'untouched';

      $url->setarg('status[' . $key . '][]', $module);
    }

    modman::updateDB();

    return new Redirect($url->string());
  }

  public static function rpc_rebuild(Context $ctx)
  {
    Loader::rebuild();
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
        if (file_exists($path))
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
