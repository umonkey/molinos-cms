<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModManRPC extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.modman
   */
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
    $ctx->registry->rebuild();
    $ctx->registry->broadcast('ru.molinos.cms.install', array($ctx));
  }

  public static function rpc_post_configure(Context $ctx)
  {
    $conf = array();

    $ctx->db->beginTransaction();
    $ctx->user->checkAccess('u', 'moduleinfo');

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

      $cfg = get_test_context()->config;
      $cfg->debuggers = $debuggers;
      $cfg->write();

      if (array_key_exists('debuggers', $conf))
        unset($conf['debuggers']);
    }

    if (count($tmp = array_values(Node::find($ctx->db, array('class' => 'moduleinfo', 'name' => $module)))))
      $node = $tmp[0];
    else
      $node = Node::create('moduleinfo', array(
        'name' => $module,
        'published' => true,
        ));

    mcms::flog($module . ': configuration updated.');

    $node->config = $conf;
    $node->save();
    $ctx->db->commit();

    Structure::getInstance()->drop();
  }

  /**
   * Изменение списка активных модулей.
   */
  public static function rpc_addremove(Context $ctx)
  {
    $status = array();
    $enabled = $ctx->post('modules');

    // Удаляем отключенные модули.
    foreach (modman::getLocalModules() as $name => $info) {
      if ('required' != $info['priority'] and !in_array($name, $enabled)) {
        // Отказываемся удалять локальные модули, которые нельзя вернуть.
        if (!empty($info['url'])) {
          if (modman::uninstall($name))
            $status[$name] = 'removed';
        }
      }
    }

    // Загружаем отсутствующие модули.
    foreach (modman::getAllModules() as $name => $info) {
      if (in_array($name, $enabled) and !modman::isInstalled($name)) {
        if (!modman::install($name))
          $status[$name] = 'failed';
        else
          $status[$name] = 'installed';
      }
    }

    $next = new url($ctx->get('destination', '?q=admin'));
    $next->setarg('status', $status);

    self::rpc_rebuild($ctx);
    Structure::getInstance()->rebuild();

    mcms::flush();
    mcms::flush(mcms::FLUSH_NOW);

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
}
