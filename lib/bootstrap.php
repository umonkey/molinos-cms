<?php

// Текущая версия.
define('MCMS_VERSION', '8.12');

// Начало обработки запроса, для замеров производительности.
define('MCMS_START_TIME', microtime(true));

define('MCMS_LIB', dirname(realpath(__FILE__)));
define('MCMS_ROOT', dirname(MCMS_LIB));

chdir(MCMS_ROOT);

set_include_path(MCMS_ROOT);

require implode(DIRECTORY_SEPARATOR, array('lib', 'modules', 'base', 'class.os.php'));

class Loader
{
  private static $map = null;

  // Подгружает карту классов, если ещё не загружена.
  private static function load()
  {
    if (null === self::$map) {
      foreach (array('classpath.inc', 'classpath.base.inc') as $key) {
        if (file_exists($path = os::path(MCMS_LIB, $key)) and is_array($tmp = include $path)) {
          self::$map = $tmp;
          break;
        }
      }

      if (null === self::$map) {
        // Исключение обрабатываем именно здесь потому, что именно здесь
        // глобального обработчика ещё нет — это инициализация системы.
        // Если не удастся записать файл при обычном вызове rebuild()
        // по запросу пользователя, это будет выполняться в штатном
        // режиме, с нормальным обработчиком.
        try {
          self::rebuild();
        } catch (Exception $e) {
          header('Content-Type: text/plain; charset=utf-8');
          die($e->getMessage());
        }

        self::load();
      }
    }
  }

  public static function rebuild($local = false)
  {
    $path = MCMS_LIB . DIRECTORY_SEPARATOR . ($local
      ? 'classpath.base.inc'
      : 'classpath.inc');

    os::writeArray($path, self::scan($local), true);
  }

  private static function scan($local = false)
  {
    $result = array(
      'classes' => array(),
      'rclasses' => array(),
      'interfaces' => array(),
      );

    $modules = glob(os::path(MCMS_LIB, 'modules', '*', 'module.ini'));

    foreach ($modules as $modinfo) {
      $path = dirname($modinfo);
      $modname = basename($path);

      if (!is_readable($modinfo)) {
        if (class_exists('mcms'))
          mcms::flog($modinfo . ': not readable.');
        continue;
      }

      if (!is_array($ini = parse_ini_file($modinfo, true))) {
        if (class_exists('mcms'))
          mcms::flog($modinfo . ': garbage.');
        continue;
      }

      if ($local and 'required' != $ini['priority'])
        continue;

      self::scanModule($modname, $path, $result);
    }

    ksort($result['classes']);

    foreach ($result['interfaces'] as $k => $v) {
      $result['interfaces'][$k] = array_unique($v);
      sort($result['interfaces'][$k]);
    }

    ksort($result['interfaces']);

    return $result;
  }

  private static function scanModule($modname, $path, array &$result)
  {
    foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') as $classpath) {
      $parts = explode('.', basename($classpath), 3);

      if (count($parts) != 3 or $parts[2] != 'php')
        continue;

      if (!is_readable($classpath))
        continue;

      $classname = null;

      switch ($type = strtolower($parts[0])) {
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

      if (true) {
        // Добавляем в список только первый найденный класс.
        if (!array_key_exists($classname, $result['classes'])) {
          $result['classes'][$classname] = os::localpath($classpath);
          $result['rclasses'][$classname] = $modname;
        }

        // Строим список интерфейсов.
        if ($type !== 'interface') {
          if (preg_match('@^\s*(abstract\s+){0,1}class\s+([^\s]+)(\s+extends\s+([^\s]+))*(\s+implements\s+([^\n\r]+))*@im', file_get_contents($classpath), $m)) {
            $classname = $m[2];

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

            foreach ($interfaces as $i)
              $result['interfaces'][strtolower($i)][] = strtolower($classname);
          }
        }
      }
    }
  }

  public static function getClassPath($className, $local = false)
  {
    self::load();

    $className = strtolower($className);

    if (!array_key_exists($className, self::$map['classes']))
      return null;

    $path = self::$map['classes'][$className];

    return $local
      ? os::localpath($path)
      : $path;
  }

  public static function autoload($className)
  {
    if (null !== ($path = self::getClassPath($className))) {
      if (!file_exists($path)) {
        mcms::flog($className . ' is in a file which does not exist.');
        return;
      }
      elseif (!is_readable($path)) {
        throw new RuntimeException("{$className} is in a file which "
          ."is read-protected: {$path}");
      }

      include $path;
    }
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

  /**
   * Возвращает имена классов, реализующих интерфейс.
   * При необходимости ограничивает классы модулем.
   */
  public static function getImplementors($interface, $module = null)
  {
    self::load();

    $list = empty(self::$map['interfaces'][$interfaces = strtolower($interface)])
      ? array()
      : self::$map['interfaces'][$interfaces];

    if (null !== $module)
      $list = array_values(array_intersect($list,
        array_keys(array_intersect(self::$map['rclasses'], array(strtolower($module))))));

    return $list;
  }
}

// FIXME: вынести mcms::check() куда-нибудь сюда, чтобы консольные
// скрипты его прозрачно использовали.

if (function_exists('mb_internal_encoding'))
  mb_internal_encoding('UTF-8');

spl_autoload_register(array('Loader', 'autoload'));

// FIXME: вынести в класс util.
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bebop_functions.php';
