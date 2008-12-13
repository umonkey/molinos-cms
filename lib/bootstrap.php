<?php

// Текущая версия.
define('MCMS_VERSION', '8.05.6199B1');

// Начало обработки запроса, для замеров производительности.
define('MCMS_START_TIME', microtime(true));

define('MCMS_LIB', dirname(realpath(__FILE__)));
define('MCMS_ROOT', dirname(MCMS_LIB));

chdir(MCMS_ROOT);

require implode(DIRECTORY_SEPARATOR, array('lib', 'modules', 'base', 'class.os.php'));

class Loader
{
  public static function rebuild($local = false)
  {
    $path = MCMS_LIB . DIRECTORY_SEPARATOR . ($local
      ? 'classpath.local.inc'
      : 'classpath.inc');

    $enabled = class_exists('mcms')
      ? mcms::config('runtime.modules')
      : array('admin');

    os::writeArray($path, self::scan($local, $enabled));
  }

  private static function scan($local = false, $enabled_modules = null)
  {
    $result = array(
      'modules' => array(),
      'classes' => array(),
      'interfaces' => array(),
      );

    $folder = $local
      ? 'modules.local'
      : 'modules';

    $modules = glob(os::path(os::localpath(MCMS_LIB), $folder, '*', 'module.ini'));

    $exclude = self::exclude();

    foreach ($modules as $modinfo) {
      $path = dirname($modinfo);
      $modname = basename($path);

      if (!is_readable($modinfo)) {
        mcms::flog('bootstrap', $modinfo . ': not readable.');
        continue;
      }

      $result['modules'][$modname] = array(
        'classes' => array(),
        'interfaces' => array(),
        'implementors' => array(),
        'enabled' => $modok = ($enabled_modules === null or (is_array($enabled_modules) and in_array($modname, $enabled_modules))),
        );

      if (is_array($ini = parse_ini_file($modinfo, true))) {
        $result['modules'][$modname] = $ini;

        if ('required' == $ini['priority'])
          $modok = $result['modules'][$modname]['enabled'] = true;
      }

      if ($modok) {
        // Составляем список доступных классов.
        foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') as $classpath) {
          if (in_array($classpath, $exclude))
            continue;

          $parts = explode('.', basename($classpath), 3);

          if (count($parts) != 3 or $parts[2] != 'php')
            continue;

          $classname = null;

          switch ($type = $parts[0]) {
          case 'class':
            $classname = $parts[1];
            break;
          case 'control':
          case 'node':
          case 'widget':
          case 'exception':
            $classname = $parts[1] . $type;
            break;
          case 'interface':
            $classname = 'i'. $parts[1];
            break;
          }

          if ('' === ($classname = strtolower($classname)))
            continue;

          if (null !== $classname and is_readable($classpath)) {
            // Добавляем в список только первый найденный класс.
            if ($modok and !array_key_exists($classname, $result['classes'])) {
              $result['classes'][$classname] = $classpath;
              $result['rclasses'][$classname] = $modname;
            }

            // Строим список интерфейсов.
            if ($type !== 'interface') {
              if (preg_match('@^\s*(abstract\s+){0,1}class\s+([^\s]+)(\s+extends\s+([^\s]+))*(\s+implements\s+([^\n\r]+))*@im', file_get_contents($classpath), $m)) {
                $classname = $m[2];

                $result['modules'][$modname]['classes'][] = $classname;

                if (!empty($m[6]))
                  $interfaces = preg_split('/[,\s]+/', preg_replace('#/\*.*\*/#', '', $m[6]), -1, PREG_SPLIT_NO_EMPTY);
                else
                  $interfaces = array();

                if (!empty($m[4])) {
                  switch (strtolower($m[4])) {
                  case 'control':
                    $interfaces[] = 'iFormControl';
                    break;
                  case 'widget':
                    $interfaces[] = 'iWidget';
                    break;
                  case 'node':
                  case 'nodebase':
                    $interfaces[] = 'iContentType';
                    break;
                  }
                }

                foreach ($interfaces as $i) {
                  if (empty($result['modules'][$modname]['interfaces']) or !in_array($i, $result['modules'][$modname]['interfaces']))
                    $result['modules'][$modname]['interfaces'][] = $i;
                  $result['modules'][$modname]['implementors'][$i][] = $classname;

                  if ($modok)
                    $result['interfaces'][strtolower($i)][] = strtolower($classname);
                }
              }
            }
          }
        }
      }

      /*
      if (empty($result['modules'][$modname]['classes']))
        unset($result['modules'][$modname]);
      */
    }

    ksort($result['classes']);

    foreach ($result['interfaces'] as $k => $v) {
      $result['interfaces'][$k] = array_unique($v);
      sort($result['interfaces'][$k]);
    }

    ksort($result['interfaces']);

    return $result;
  }

  public static function getClassPath($className, $local = false)
  {
    static $map = null;

    if (null === $map)
      $map = self::map('classes');

    if (!is_array($map))
      return null;

    $k = strtolower($className);

    if (!array_key_exists($k, $map))
      return null;

    return $local
      ? os::localpath($map[$k])
      : $map[$k];
  }

  public static function autoload($className)
  {
    if (null !== ($path = self::getClassPath($className))) {
      if (!file_exists($path))
        throw new RuntimeException("{$className} is in a file which "
          ."does not exist: {$path}");
      elseif (!is_readable($path))
        throw new RuntimeException("{$className} is in a file which "
          ."is read-protected: {$path}");

      include $path;
    }
  }

  public static function map($part = null)
  {
    $map = array();

    if (file_exists($path = MCMS_LIB . DIRECTORY_SEPARATOR . 'classpath.inc'))
      if (!is_array($map = include $path))
        $map = array();

    return (null === $part)
      ? $map
      : $map[$part];
  }

  private static function exclude()
  {
    return array(
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'class.requestcontroller.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'class.adminuimodule.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'pdo' . DIRECTORY_SEPARATOR . 'class.dbschema_node__cache.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'control.attachment.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'class.apc_cache.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'class.bebopcache.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'class.dbcache.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'class.local_cache.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'class.memcached_cache.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'interface.bebopcacheengine.php',
      'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'subscription' . DIRECTORY_SEPARATOR . 'widget.subscriptionadmin.php',
      );
  }
}

// FIXME: вынести mcms::check() куда-нибудь сюда, чтобы консольные
// скрипты его прозрачно использовали.

if (function_exists('mb_internal_encoding'))
  mb_internal_encoding('UTF-8');

spl_autoload_register(array('Loader', 'autoload'));

// FIXME: вынести в класс util.
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bebop_functions.php';
