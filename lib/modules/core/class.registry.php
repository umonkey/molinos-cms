<?php

class Registry
{
  /**
   * Информация о модулях.
   */
  private $modules = array();

  /**
   * Информация об обработчиках вызовов.
   */
  private $reg = array();

  /**
   * Пути к классам.
   */
  private $paths = array();

  /**
   * Инициализация реестра, включает автозагрузку.
   */
  public function __construct()
  {
    spl_autoload_register(array($this, 'autoload'));
    register_shutdown_function(array(__CLASS__, 'on_shutdown'));
    set_error_handler(array($this, 'on_error'), defined('MCMS_ERROR_LEVEL') ? MCMS_ERROR_LEVEL : (E_ERROR|E_WARNING));
  }

  /**
   * Отключение автозагрузки.
   */
  public function __destruct()
  {
    restore_error_handler();
    spl_autoload_unregister(array($this, 'autoload'));
  }

  /**
   * Вызов всех обработчиков сообщения, с передачей параметров.
   *
   * @return Registry $this
   */
  public function broadcast($method, array $args)
  {
    if (array_key_exists($method, $this->reg))
      foreach ($this->reg[$method] as $handler)
        if (is_callable($handler))
          call_user_func_array($handler, $args);
    return $this;
  }

  /**
   * Отправляет сообщение всем подписчикам, возвращает массив с результатами.
   */
  public function poll($method, array $args = array(), $safe = false)
  {
    $result = array();

    if (array_key_exists($method, $this->reg))
      foreach ($this->reg[$method] as $handler) {
        if (is_callable($handler)) {
          try {
            $parts = explode('::', $handler);
            $result[] = array(
              'class' => $parts[0],
              'method' => $parts[1],
              'result' => call_user_func_array($handler, $args),
              );
          } catch (Exception $e) {
            if (!$safe)
              throw $e;
          }
        }
      }

    return $result;
  }

  public function enum_simple($method, array $args = array())
  {
    $result = array();
    foreach ($this->poll($method, $args) as $v)
      if (!empty($v['result']))
        $result[] = $v['result'];
    return $result;
  }

  /**
   * Вызов первого обработчика сообщения.
   *
   * @return mixed Результат вызова обработчика или false.
   */
  public function unicast($method, array $args = array())
  {
    if (array_key_exists($method, $this->reg))
      if (is_callable($handler = $this->reg[$method][0])) {
        if (false === ($result = call_user_func_array($handler, $args)))
          $result = null;
        return $result;
      }
    return false;
  }

  /**
   * Загрузка реестра из файла. Если он не найден — воссоздаётся.
   */
  public function load()
  {
    if (is_array($data = Cache::getInstance()->get('registry'))) {
      $this->reg = $data['messages'];
      $this->paths = $data['paths'];
      return true;
    }

    $this->log("could not load registry from cache");

    return false;
  }

  /**
   * Сканирует модули, формирует список файлов для загрузки классов,
   * составляет список обработчиков сообщений.
   *
   * @return Registry Ссылка на себя.
   */
  public function rebuild(array $modules = array())
  {
    $this->modules = $this->reg = $this->paths = array();
    $search = os::path(dirname(dirname(__FILE__)), '*', 'module.ini');

    $this->log('rebuilding registry from scratch');

    foreach (glob($search) as $iniFileName) {
      $ini = ini::read($iniFileName);
      $root = dirname($iniFileName);
      $moduleName = basename(dirname($iniFileName));

      if (empty($ini['priority']))
        continue;

      /*
      if ('required' != $ini['priority'] and !in_array(basename(dirname($iniFileName)), $modules))
        continue;
      */

      if (!empty($ini['classes'])) {
        foreach ($ini['classes'] as $k => $v) {
          $fileName = os::path($root, $v);
          if (0 === strpos($fileName, MCMS_ROOT))
            $fileName = ltrim(substr($fileName, strlen(MCMS_ROOT)), DIRECTORY_SEPARATOR);
          $this->paths[strtolower($k)] = $fileName;
        }
      }

      if (!empty($ini['messages'])) {
        foreach ($ini['messages'] as $k => $v) {
          foreach (explode(',', $v) as $handler)
            $this->reg[$k][] = $handler;
        }
      }

      foreach ($ini as $k => $v)
        if (!is_array($v) and !empty($v))
          $this->modules[strtolower($moduleName)][$k] = $v;
    }

    ksort($this->reg);
    ksort($this->paths);

    Cache::getInstance()->set('registry', array(
      'messages' => $this->reg,
      'paths' => $this->paths,
      ));

    return $this;
  }

  /**
   * Подгружает классы из файлов по мере обращения.
   */
  private function autoload($className)
  {
    $className = strtolower($className);
    if (array_key_exists($className, $this->paths)) {
      if (file_exists($fileName = MCMS_ROOT . DIRECTORY_SEPARATOR . $this->paths[$className]))
        require $fileName;
    }
  }

  /**
   * Обрабатывает экстренное завершение работы.
   */
  public static function on_shutdown()
  {
    if (null !== ($e = error_get_last()) and $e['type'] & (E_ERROR|E_RECOVERABLE_ERROR)) {
      self::send_error("shutdown[{$e['type']}]: {$e['message']}",
        sprintf("File:    %s\nLine:    %u\n\n", os::localpath($e['file']), $e['line']));
    }
  }

  /**
   * Дополнительная обработка ошибок.
   */
  public function on_error($errno, $errstr, $errfile, $errline, array $context)
  {
    if (error_reporting())
      self::send_error("error[{$errno}]: {$errstr}");
  }

  /**
   * Отправляет сообщение об ошибке куда следует.
   */
  private static function send_error($message = "undefined", $extra = null)
  {
    Logger::trace($message);

    $subject = "Error at " . $_SERVER['HTTP_HOST'];
    $content = "<pre>Message: {$message}\nMethod:  {$_SERVER['REQUEST_METHOD']}\n"
      . "URL:     http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\n{$extra}\n"
      . "Backtrace follows.\n\n" . Logger::backtrace() . '</pre>';

    BebopMimeMail::send(null, Context::last()->config->get('main/errors/mail'), $subject, $content);
  }

  /**
   * Сканирует классы, обновляет файлы module.ini
   */
  public function rebuildMeta()
  {
    foreach (os::find('lib', 'modules', '*', 'module.ini') as $iniFileName) {
      $moduleName = basename(dirname($iniFileName));

      if (!empty($argv[1]) and $argv[1] != $moduleName)
        continue;

      $ini = ini::read($iniFileName);
      $path = dirname($iniFileName);

      foreach ($ini as $k => $v)
        if (is_array($v))
          unset($ini[$k]);

      foreach (os::find($path, '*.php') as $fileName) {
        $baseName = basename($fileName);

        if (0 === strpos($baseName, 'test'))
          continue;
        elseif ('.test.php' == substr($baseName, -9))
          continue;

        $source = strtolower(file_get_contents($fileName));

        if (preg_match('@^\s*(?:abstract\s+)?class\s+([a-z0-9_]+)(\s+extends\s+([^\s]+))*(\s+implements\s+([^\n\r]+))*@m', $source, $m)) {
          $className = $m[1];
          $ini['classes'][$m[1]] = $baseName;

          if (preg_match_all('#(?:@mcms_message\s+)([a-z0-9.]+)(?:[^{]*public\s+static\s+function\s+)([^(]+)#s', $source, $m)) {
            foreach ($m[1] as $idx => $message) {
              $method = $m[2][$idx];
              $ini['messages'][$message][] = $className . '::' . $method;
            }
          }
        }

        elseif (preg_match('@^\s*interface\s+([a-z0-9_]+)@m', $source, $m))
          $ini['classes'][$m[1]] = $baseName;
      }

      $ini['changelog'] = 'http://molinos-cms.googlecode.com/svn/dist/' . MCMS_RELEASE . '/changelogs/' . $moduleName . '.txt';

      if (!empty($ini['classes']))
        ksort($ini['classes']);

      ini::write($iniFileName, $ini);
    }
  }

  /**
   * Выводит сообщение в лог.
   */
  private function log($message)
  {
    error_log(trim($message));
  }
}
