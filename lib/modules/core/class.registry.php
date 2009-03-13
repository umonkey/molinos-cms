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
  }

  /**
   * Отключение автозагрузки.
   */
  public function __destruct()
  {
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
    if (file_exists($path = $this->getSavePath())) {
      $data = include($path);
      $this->modules = $data['modules'];
      $this->reg = $data['messages'];
      $this->paths = $data['paths'];
      return true;
    }

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
    $this->reg = array();

    $search = os::path(dirname(dirname(__FILE__)), '*', 'module.ini');

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

    os::writeArray($this->getSavePath(), array(
      'modules' => $this->modules,
      'messages' => $this->reg,
      'paths' => $this->paths,
      ), true);

    return $this;
  }

  private function getSavePath()
  {
    return os::path(MCMS_ROOT, MCMS_SITE_FOLDER, '.registry.php');
  }

  /**
   * Подгружает классы из файлов по мере обращения.
   */
  private function autoload($className)
  {
    $className = strtolower($className);
    if (array_key_exists($className, $this->paths))
      require MCMS_ROOT . DIRECTORY_SEPARATOR . $this->paths[$className];
  }
}
