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
  protected $schema = array();
  protected $templates = array();

  public static function getInstance()
  {
    if (null === self::$instance)
      self::$instance = new Structure();
    return self::$instance;
  }

  public function __construct()
  {
    // Загружается по требованию.
    // $this->load();
  }

  private function load()
  {
    if (!file_exists($file = $this->getFileName()))
      $this->rebuild();

    if (!file_exists($file))
      throw new RuntimeException(t('Не удалось загрузить структуру сайта.'));

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

    $overrides = array();

    if (null === ($domain = $this->findDomain($domain, $overrides)))
      return false;

    $path = '/' . rtrim($path, '/');
    $match = null;

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

    if (null === $match)
      return false;

    $result = array(
      'name' => ('/' == $match)
        ? 'index'
        : str_replace('/', '-', ltrim($match, '/')),
      'page' => $this->domains[$domain][$match],
      'args' => $args,
      );

    foreach ($overrides as $k => $v)
      $result['page'][$k] = $v;

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
    if (empty($page['params']))
      return empty($path_args)
        ? array()
        : null;

    $keys = explode('+', $page['params']);

    if (empty($page['params']))
      mcms::debug($page, $keys, $path_args);

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
  private function findDomain($host, array &$overrides)
  {
    if (array_key_exists($host, $this->aliases)) {
      $overrides = $this->aliases[$host];
      unset($overrides['target']);
      return $this->aliases[$host]['target'];
    }

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
   * Возвращает структуру типа документа.
   */
  public function findSchema($class, $default = false)
  {
    if (!$this->loaded)
      $this->load();

    if (array_key_exists($class, $this->schema))
      return $this->schema[$class];
    else
      return $default;
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
      os::writeArray($this->getFileName(), array(
        'widgets' => $this->widgets,
        'aliases' => $this->aliases,
        'domains' => $this->domains,
        'schema' => $this->schema,
        'access' => $this->access,
        'templates' => $this->templates,
        ));
  }

  public function drop()
  {
    if (file_exists($file = $this->getFileName()))
      unlink($file);
  }

  public function getTemplateEngines()
  {
    if (!$this->loaded)
      $this->load();

    if (empty($this->templates))
      $this->rebuild();

    return $this->templates;
  }
}
