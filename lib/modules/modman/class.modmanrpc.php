<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModManRPC extends RPCHandler
{
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function rpc_update(Context $ctx)
  {
    modman::updateDB();
    self::rpc_rebuild($ctx);

    $next = new url($ctx->get('destination'));
    $next->setarg('status', null);
    return new Redirect($next->string());
  }

  public static function rpc_post_upgrade(Context $ctx)
  {
    $selected = $ctx->post('all')
      ? array_keys(modman::getUpdatedModules())
      : $ctx->post('modules', array());

    $errors = array();

    foreach (modman::getUpdatedModules() as $moduleName => $moduleInfo)
      if (in_array($moduleName, $selected))
        if (!modman::updateModule($moduleName))
          $errors[] = $moduleName;

    modman::updateDB();
    self::rpc_rebuild($ctx);

    $url = new url($ctx->get('destination'));
    $url->setarg('errors', join('+', $errors));

    return new Redirect($url->string());
  }

  public static function rpc_rebuild(Context $ctx)
  {
    // Обновление БД.
    foreach (os::find('lib/modules/*/*.yml') as $fileName) {
      $schema = Spyc::YAMLLoad($fileName);
      Logger::log('applying ' . $fileName);
      if (!empty($schema['tables'])) {
        foreach ($schema['tables'] as $tableName => $tableInfo)
          TableInfo::check($tableName, $tableInfo);
      }
    }


    $ctx->registry->rebuild();
    $ctx->registry->broadcast('ru.molinos.cms.install', array($ctx));
  }

  public static function rpc_post_configure(Context $ctx)
  {
    $conf = array();

    if (!($moduleName = $ctx->get('module')))
      throw new RuntimeException(t('Не указано имя настраиваемого модуля.'));

    $conf = Control::data();
    foreach (modman::settings_get($ctx, $moduleName) as $k => $v)
      $v->set($ctx->post($k, $v->default), $conf);

    $ctx->config->set('modules/' . $moduleName, $conf->dump())->save();

    mcms::flog($moduleName . ': configuration updated.');

    Structure::getInstance()->drop();
  }

  public static function rpc_post_install(Context $ctx)
  {
    $status = array();
    $enabled = (array)$ctx->post('modules');

    // Загружаем отсутствующие модули.
    foreach (modman::getAllModules() as $name => $info) {
      if (in_array($name, $enabled) and !modman::isInstalled($name)) {
        if (!modman::install($name))
          $status[$name] = 'failed';
        else
          $status[$name] = 'installed';
      }
    }

    $ctx->config->modules = $enabled;
    $ctx->config->save();

    $next = new url($ctx->get('destination', '?q=admin'));
    $next->setarg('status', $status);

    self::rpc_rebuild($ctx);
    Structure::getInstance()->rebuild();

    // Обновляем базу модулей, чтобы выбросить удалённые локальные.
    modman::updateDB();

    return new Redirect($next->string());
  }

  public static function rpc_post_remove(Context $ctx)
  {
    $status = array();
    $remove = (array)$ctx->post('modules');

    // Удаляем отключенные модули.
    foreach (modman::getLocalModules() as $name => $info) {
      if ('required' != @$info['priority'] and in_array($name, $remove)) {
        // Отказываемся удалять локальные модули, которые нельзя вернуть.
        if (!empty($info['url'])) {
          if (modman::uninstall($name))
            $status[$name] = 'removed';
        }
      }
    }

    /*
    $ctx->config->modules = $enabled;
    $ctx->config->write();
    */

    $next = new url($ctx->get('destination', 'admin'));
    $next->setarg('status', $status);

    self::rpc_rebuild($ctx);
    Structure::getInstance()->rebuild();

    // Обновляем базу модулей, чтобы выбросить удалённые локальные.
    modman::updateDB();

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

        $new = $path;

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
  }

  /**
   * Возвращает информацию о наличиях обновлений.
   */
  public static function on_get_updates(Context $ctx)
  {
    $output = '';

    foreach (modman::getupdatedModules() as $name => $info)
      $output .= html::em('module', array_merge($info, array(
        'id' => $name,
        )));

    return new Response(html::em('modules', $output), 'text/xml');
  }
}
