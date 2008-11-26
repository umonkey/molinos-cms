<?php
/**
 * Класс для работы со структурой сайта.
 *
 * Пример использования:
 *
 * $s = new Structure();
 * $p = $s->findPage($_SERVER['HTTP_HOST'], $_GET['q']);
 *
 * Возвращает FALSE, если подходящая страница не найдена, или
 * массив с ключами: (string)name, (array)page, (array)args.
 *
 * Justin Forest, 2008-11-15.
 */

class Structure
{
  private static $instance = null;

  private $loaded = false;
  protected $widgets = array();
  protected $aliases = array();
  protected $domains = array();
  protected $access = array();

  public static function getInstance()
  {
    if (null === self::$instance)
      self::$instance = new Structure();
    return self::$instance;
  }

  public function __construct()
  {
    // $this->load();
  }

  private function load()
  {
    if (!file_exists($file = $this->getFileName()))
      $this->rebuild();

    $data = include($file);

    if (!is_array($data))
      throw new RuntimeException(t('Считанная из файла структура системы содержит мусор.'));

    foreach ($data as $k => $v)
      $this->$k = $v;

    $this->loaded = true;
  }

  /**
   * Пересоздание структуры сайта.
   */
  public function rebuild()
  {
    $ma = new StructureMA();

    foreach ($ma->import() as $k => $v)
      $this->$k = $v;

    $this->loaded = true;

    $this->save();
  }

  /**
   * Возвращает имя файла, содержащего конфигурацию в PHP.
   */
  protected function getFileName()
  {
    return Config::getInstance()->getBaseName() . '.structure.php';
  }

  /**
   * Возвращает информацию о подходящей странице.
   */
  public function findPage($domain, $path)
  {
    if (!$this->loaded)
      $this->load();

    if (null === ($domain = $this->findDomain($domain)))
      return false;

    $path = '/' . rtrim($path, '/');
    $args = array();

    $match = '/';
    $args = trim($path, '/')
      ? explode('/', trim($path, '/'))
      : array();

    foreach ($this->domains[$domain] as $page => $meta) {
      if (strlen($page) > strlen($match)) {
        // Точное совпадение.
        if ($path == $page) {
          $match = $page;
          $args = array();
          break;
        }

        if (0 === strpos($path, $page)) {
          if ('/' == substr($path, strlen($page), 1)) {
            $args = explode('/', trim(substr($path, strlen($page) + 1), '/'));

            // У страницы нет параметров, однако в запросе они есть — не то.
            if (empty($meta['params']) and !empty($args))
              continue;

            // У страницы меньше параметров, чем есть в запросе — не то.
            if (count($args) > count(explode('+', $meta['params'])))
              continue;

            $match = $page;
          }
        }
      }
    }

    $result = array(
      'name' => ('/' == $match)
        ? 'index'
        : str_replace('/', '-', ltrim($match, '/')),
      'page' => $this->domains[$domain][$match],
      'args' => $args,
      );

    if (empty($result['page']['published']))
      return false;

    if (false === ($result['args'] = $this->findPageParameters($result['page'], $args)))
      return false;

    return $result;
  }

  /**
   * Возвращает параметры страницы в виде массива или false в случае ошибки.
   */
  private function findPageParameters(array $page, array $path_args)
  {
    $keys = array_key_exists('params', $page)
      ? explode('+', $page['params'])
      : array();

    // Параметров в урле больше, чем должно быть => мусор => 404.
    if (count($path_args) > count($keys))
      return false;

    foreach ($path_args as $arg)
      if (!is_numeric($arg))
        throw new PageNotFoundException();

    $result = array();

    foreach ($keys as $k => $v)
      $result[$v] = isset($path_args[$k])
        ? $path_args[$k]
        : null;

    return $result;
  }

  /**
   * Возвращает домен с учётом алиасов.
   */
  private function findDomain($host)
  {
    if (array_key_exists($host, $this->aliases))
      return $this->aliases[$host]['target'];

    if (array_key_exists($host, $this->domains))
      return $host;

    if (!empty($this->domains))
      return array_shift(array_keys($this->domains));

    return null;
  }

  /**
   * Возвращает информацию об указанных виджетах.
   */
  public function findWidgets(array $names)
  {
    if (!$this->loaded)
      $this->load();

    $result = array();

    foreach ($names as $name)
      if (array_key_exists($name, $this->widgets))
        $result[$name] = $this->widgets[$name];

    return $result;
  }

  /**
   * Возвращает суммарные права для набора прав.
   */
  public function getGroupAccess(array $groups)
  {
    if (!$this->loaded)
      $this->load();

    $result = empty($this->access['groups']['anonymous'])
      ? array()
      : $this->access['groups']['anonymous'];

    foreach ($groups as $gid) {
      if ($gid) {
        if (array_key_exists($gid = 'group:' . $gid, $this->access['groups'])) {
          foreach ($this->access['groups'][$gid] as $mode => $types) {
            if (array_key_exists($mode, $result))
              $result[$mode] = array_unique(array_merge($result[$mode], $types));
            else
              $result[$mode] = $types;
          }
        }
      }
    }

    return $result;
  }

  /**
   * Возвращает права на собственные объекты нужного типа.
   */
  public function getOwnDocAccess($type)
  {
    if (empty($this->access['types'][$type]))
      return array();
    else
      return $this->access['types'][$type];
  }

  /**
   * Запись структуры в файл.
   */
  public function save()
  {
    if ($this->loaded)
      mcms::writeFile($this->getFileName(), array(
        'widgets' => $this->widgets,
        'aliases' => $this->aliases,
        'domains' => $this->domains,
        'access' => $this->access,
        ));
  }

  public function drop()
  {
    if (file_exists($file = $this->getFileName()))
      unlink($file);
  }
}
