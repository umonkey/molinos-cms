<?php

class modman
{
  const cachekey = 'modules.ini';

  /**
   * Возвращает информацию обо всех модулях.
   */
  public static function getAllModules()
  {
    if (!is_array($cached = Cache::getInstance()->get(self::cachekey)))
      $cached = array();
    return $cached;
  }

  /**
   * Возвращает информацию о модулях, которые можно обновить.
   */
  public static function getUpdatedModules()
  {
    $result = self::getAllModules();

    foreach ($result as $name => $info)
      if (!self::canUpdateModule($name, $info))
        unset($result[$name]);

    return $result;
  }

  /**
   * Возвращает true, если модуль может быть обновлён:
   * если он установлен и не является локальным (был
   * установлен извне).
   */
  private static function canUpdateModule($name, array $info)
  {
    if (!modman::isInstalled($name))
      return false;

    if (empty($info['url']))
      return false;

    if (empty($info['version.local']))
      return false;

    if (version_compare($info['version.local'], $info['version'], '>='))
      return false;

    return true;
  }

  /**
   * Возвращает информацию о локальных модулях.
   */
  public static function getLocalModules()
  {
    foreach ($result = self::getAllModules() as $name => $info)
      if (empty($info['installed']))
        unset($result[$name]);

    return $result;
  }

  /**
   * Обновление информации о доступных модулях.
   */
  public static function updateDB()
  {
    $modules = array();

    // Получение информации из внешних источников.
    foreach (self::getSources() as $url) {
      try {
        $file = http::fetch($url . '?random=' . rand(), http::NO_CACHE);
      } catch (Exception $e) {
        $file = null;
      }

      if ($file) {
        $ini = ini::read($file);

        if (empty($ini['url_prefix']) or !is_string($ini['url_prefix']))
          $ini['url_prefix'] = 'http://molinos-cms.googlecode.com/files/';

        foreach ($ini as $k => $v) {
          if (is_array($v)) {
            if (empty($v['url']))
              $v['url'] = $ini['url_prefix'] . $v['filename'];

            if (!array_key_exists($k, $modules))
              $modules[$k] = $v;
            elseif (version_compare($v['version'], $modules[$k]['version'], '>'))
              $modules[$k] = $v;
          }
        }
      }
    }

    // Добавление информации о локальных модулях.
    foreach (glob(os::path('lib', 'modules', '*')) as $dir) {
      if (!is_dir($dir))
        continue;

      $name = basename($dir);

      // Информация о модуле существует, загружаем.
      if (file_exists($file = os::path($dir, 'module.ini'))) {
        $ini = ini::read($file);

        if (array_key_exists('version', $ini)) {
          $ini['version.local'] = $ini['version'];
          unset($ini['version']);
        }

        if (!array_key_exists($name, $modules))
          $modules[$name] = $ini;
        else
          $modules[$name]['version.local'] = $ini['version.local'];

        $modules[$name]['installed'] = true;
      }

      // Информации о файле нет, устанавливаем версию 0.0
      // для возможности обновления.
      elseif (!array_key_exists($name, $modules)) {
        $modules[$name] = array(
          'name' => t('Неопознанный модуль.'),
          'version.local' => '0.0',
          'section' => 'custom',
          'priority' => 'optional',
          'installed' => true,
          );
      }
    }

    Logger::log('module info updated, ' . count($modules) . ' module(s) available.');

    Cache::getInstance()->set(self::cachekey, $modules);

    return $modules;
  }

  /**
   * Обновление конкретного модуля.
   */
  public static function updateModule($name)
  {
    $db = self::getAllModules();

    if (!array_key_exists($name, $db))
      throw new RuntimeException(t('Нет информации о модуле %name.', array(
        '%name' => $name,
        )));

    if (empty($db[$name]['url'])) {
      Logger::log("no url for module {$name}, not updated.");
      return false;
    }

    $head = http::head($url = $db[$name]['url']);

    if (200 != $head['_status']) {
      Logger::log('updateModule: file not found: ' . $url);
      return false;
    }

    $tmp = http::fetch($url);

    foreach (array('sha1' => 'sha1_file') as $k => $func) {
      if (!empty($db[$name][$k]) and $db[$name][$k] != $func($tmp)) {
        Logger::log($k . ' hash mismatch for ' . $url);
        return false;
      }
    }

    $existed = is_dir($path = os::path('lib', 'modules', $name));

    zip::unzipToFolder($tmp, $path);

    if ($existed)
      Logger::log($name . ': updated from v' . $db[$name]['version.local'] . ' to v' . $db[$name]['version'] . '.');
    else
      Logger::log($name . ': installed v' . $db[$name]['version'] . '.');

    return true;
  }

  /**
   * Возвращает список источников обновлений.
   */
  public static function getSources()
  {
    if (!is_array($urls = mcms::config('sources')))
      $urls = array();

    $default = 'http://molinos-cms.googlecode.com/svn/dist/' . mcms::version(mcms::VERSION_RELEASE) . '/modules.ini';

    if (!in_array($default, $urls))
      $urls[] = $default;

    asort($urls);

    return $urls;
  }

  /**
   * Деинсталляция модуля.
   */
  public static function uninstall($moduleName)
  {
    $inipath = os::path('lib', 'modules', $moduleName, 'module.ini');

    if (file_exists($inipath)) {
      $ini = ini::read($inipath);
      if ('required' == $ini['priority'])
        return false;
    }

    os::rmdir(dirname($inipath));

    Logger::log($moduleName . ': uninstalled.');
    return true;
  }

  /**
   * Инсталляция модуля.
   */
  public static function install($moduleName)
  {
    if (self::isInstalled($moduleName))
      return true;
    return self::updateModule($moduleName);
  }

  /**
   * Проверяет, установлен ли модуль.
   */
  public static function isInstalled($moduleName)
  {
    return is_dir(os::path('lib', 'modules', $moduleName));
  }

  public static function settings_get(Context $ctx, $moduleName)
  {
    $schema = $ctx->registry->unicast($msg = 'ru.molinos.cms.module.settings.' . $moduleName, array($ctx));
    if (!($schema instanceof Schema))
      throw new RuntimeException(t('Не удалось получить форму настройки модуля %name (сообщение %msg не обработано).', array(
        '%name' => $moduleName,
        '%msg' => $msg,
        )));
    return $schema;
  }

  public static function settings(Context $ctx, $query, array $pathinfo)
  {
    // Проверяем права, если неверно указан обработчик.
    if ('AdminPage::serve' != $pathinfo['call']) {
      if (empty($pathinfo['perms']))
        $pathinfo['perms'] = 'debug';
      AdminPage::checkperm($ctx, $pathinfo);
    }

    $name = substr(strrchr($query, '/'), 1);

    $schema = self::settings_get($ctx, $name);
    $form = $schema->getForm();
    $data = $ctx->config->get('modules/' . $name, array());

    $title = $pathinfo['title'];

    if (empty($form->title))
      $form->title = $title;

    $form->action = '?q=modman.rpc&action=configure&module=' . urlencode($name)
      . '&destination=admin/system/settings';

    $form->addControl(new SubmitControl(array(
      'text' => t('Сохранить'),
      )));

    $result = html::em('content', array(
      'name' => 'form',
      'title' => $name,
      'mode' => 'config',
      ), $form->getXML(Control::data($data)));

    $page = new AdminPage($result);
    return $page->getResponse($ctx);
  }
}
