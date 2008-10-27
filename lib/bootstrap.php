<?php

// Текущая версия.
define('MCMS_VERSION', '8.05.6194');

define('MCMS_LIB', dirname(realpath(__FILE__)));
define('MCMS_ROOT', dirname(MCMS_LIB));

class Loader
{
  public static function rebuild($local = false)
  {
    $map = self::scan($mode);

    $path = MCMS_LIB . DIRECTORY_SEPARATOR . ($local
      ? 'classpath.local.inc'
      : 'classpath.inc');

    $data = '<?php return ' . var_export($map, true) .';';

    file_put_contents($path, $data)
      or mcms::fatal('Could not update classpath.inc');
  }

  private static function scan($local = false)
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
      'modules',
      '*',
      'module.info')));

    foreach ($modules as $modinfo) {
      $path = dirname($modinfo);
      $modname = basename($path);

      $result['modules'][$modname] = array(
        'classes' => array(),
        'interfaces' => array(),
        'implementors' => array(),
        'enabled' => $modok = true, // in_array($modname, $enabled),
        );

      if (is_array($ini = parse_ini_file($modinfo, true))) {
        // Копируем базовые свойства.
        foreach (array('group', 'version', 'name', 'docurl') as $k) {
          if (array_key_exists($k, $ini)) {
            $result['modules'][$modname][$k] = $ini[$k];

            if ('group' == $k and 'core' == strtolower($ini[$k]))
              $modok = $result['modules'][$modname]['enabled'] = true;
          }
        }
      }

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
                switch ($m[4]) {
                case 'Control':
                  $interfaces[] = 'iFormControl';
                  break;
                case 'Widget':
                  $interfaces[] = 'iWidget';
                  break;
                case 'Node':
                case 'NodeBase':
                  $interfaces[] = 'iContentType';
                  break;
                }
              }

              foreach ($interfaces as $i) {
                if (!in_array($i, $result['modules'][$modname]['interfaces']))
                  $result['modules'][$modname]['interfaces'][] = $i;
                $result['modules'][$modname]['implementors'][$i][] = $classname;

                if ($modok)
                  $result['interfaces'][$i][] = $classname;
              }
            }
          }
        }
      }

      if (empty($result['modules'][$modname]['classes']))
        unset($result['modules'][$modname]);
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

  public static function autoload($className)
  {
    static $map = null;

    if (null === $map)
      $map = self::map('classes');

    $k = strtolower($className);

    if (array_key_exists($k, $map)) {
      if (!file_exists($map[$k]))
        throw new RuntimeException("{$className} is in a file which "
          ."does not exist: {$map[$k]}");
      elseif (!is_readable($map[$k]))
        throw new RuntimeException("{$className} is in a file which "
          ."is read-protected: {$map[$k]}");

      include $map[$k];

      $isif = (substr($className, 0, 1) === 'i');

      if ($isif and !in_array($className, get_declared_interfaces()))
        throw new RuntimeException("There is no {$className} interface "
          ."in {$map[$k]}.\nAPC freaks out this way sometimes.");
      elseif (!$isif and !in_array($className, get_declared_classes()))
        throw new RuntimeException("There is no {$className} class "
          ."in {$map[$k]}");
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
