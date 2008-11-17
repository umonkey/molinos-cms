<?php

// Текущая версия.
define('MCMS_VERSION', '8.05.6196');

// Начало обработки запроса, для замеров производительности.
define('MCMS_START_TIME', microtime(true));

define('MCMS_LIB', dirname(realpath(__FILE__)));
define('MCMS_ROOT', dirname(MCMS_LIB));

class Loader
{
  public static function rebuild($local = false)
  {
    $map = self::scan($local, preg_split('/,\s*/', mcms::config('runtime_modules')));

    $path = MCMS_LIB . DIRECTORY_SEPARATOR . ($local
      ? 'classpath.local.inc'
      : 'classpath.inc');

    $data = '<?php return ' . var_export($map, true) .';';

    file_put_contents($path, $data)
      or mcms::fatal('Could not update classpath.inc');
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

    $modules = glob(join(DIRECTORY_SEPARATOR, array(
      self::localpath(MCMS_LIB),
      $folder,
      '*',
      'module.info')));

    foreach ($modules as $modinfo) {
      $path = dirname($modinfo);
      $modname = basename($path);

      $result['modules'][$modname] = array(
        'classes' => array(),
        'interfaces' => array(),
        'implementors' => array(),
        'enabled' => $modok = ($enabled_modules === null or (is_array($enabled_modules) and in_array($modname, $enabled_modules))),
        );

      if (is_array($ini = parse_ini_file($modinfo, true))) {
        // Копируем базовые свойства.
        foreach (array('group', 'version', 'name', 'docurl') as $k) {
          if (array_key_exists($k, $ini)) {
            $result['modules'][$modname][$k] = $ini[$k];

            if ('group' == $k and !strcasecmp('core', $ini[$k]))
              $modok = $result['modules'][$modname]['enabled'] = true;
          }
        }
      }

      if ($modok) {
        // Составляем список доступных классов.
        foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') as $classpath) {
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
                  if (!in_array($i, $result['modules'][$modname]['interfaces']))
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

  private static function localpath($path)
  {
    if (0 === strpos($path, MCMS_ROOT))
      return substr($path, strlen(MCMS_ROOT) + 1);
    else
      return $path;
  }

  public static function getClassPath($className, $local = false)
  {
    static $map = null;

    if (null === $map)
      $map = self::map('classes');

    $k = strtolower($className);

    if (!array_key_exists($k, $map))
      return null;

    return $local
      ? self::localpath($map[$k])
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
}

spl_autoload_register(array('Loader', 'autoload'));

// FIXME: вынести в класс util.
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bebop_functions.php';
