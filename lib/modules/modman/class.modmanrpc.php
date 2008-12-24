<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModManRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_update(Context $ctx)
  {
    modman::updateDB();
    Loader::rebuild();

    $next = new url($ctx->get('destination'));
    $next->setarg('status', null);
    return new Redirect($next->string());
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

  public static function rpc_configure(Context $ctx)
  {
    $conf = array();

    mcms::user()->checkAccess('u', 'moduleinfo');

    foreach ($ctx->post as $k => $v) {
      if (substr($k, 0, 7) == 'config_' and !empty($v)) {
        if (is_array($v) and array_key_exists('__reset', $v))
          unset($v['__reset']);
        if (!empty($v))
          $conf[substr($k, 7)] = $v;
      }
    }

    if ('admin' == ($module = $ctx->get('module'))) {
      $debuggers = empty($conf['debuggers'])
        ? null
        : preg_split('/,\s*/', $conf['debuggers'], -1, PREG_SPLIT_NO_EMPTY);

      $cfg = Config::getInstance();
      $cfg->debuggers = $debuggers;
      $cfg->write();

      if (array_key_exists('debuggers', $conf))
        unset($conf['debuggers']);
    }

    if (count($tmp = array_values(Node::find(array('class' => 'moduleinfo', 'name' => $module)))))
      $node = $tmp[0];
    else
      $node = Node::create('moduleinfo', array(
        'name' => $module,
        'published' => true,
        ));

    mcms::flog($module . ': configuration updated.');

    $node->config = $conf;
    $node->save();
  }

  /**
   * Изменение списка активных модулей.
   */
  public static function rpc_addremove(Context $ctx)
  {
    $failed = $ok = array();
    $enabled = $ctx->post('modules');

    // Удаляем отключенные модули.
    foreach (modman::getLocalModules() as $name => $info)
      if (!in_array($name, $enabled))
        modman::uninstall($name);

    // Загружаем отсутствующие модули.
    foreach (modman::getAllModules() as $name => $info) {
      if (in_array($name, $enabled))
        if (!modman::install($name))
          $failed[] = $name;
    }

    $next = new url($ctx->get('destination', '?q=admin'));
    $next->setarg('status.failed', implode(',', $failed));

    $config = Config::getInstance();
    $config->set('runtime.modules', array_diff($enabled, $failed));
    $config->write();

    Loader::rebuild();
    Structure::getInstance()->rebuild();

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);

    return new Redirect($next->string());
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
