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
  const version = 1;

  private static $instance = null;

  private $loaded = false;
  protected $widgets = array();
  protected $aliases = array();
  protected $domains = array();
  protected $access = array();
  protected $schema = array();
  protected $modules = array();

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

    if (empty($data['version']) or $data['version'] < self::version) {
      $this->rebuild();
      $data = include($file);
    }

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
    $path = MCMS_ROOT . DIRECTORY_SEPARATOR . Context::last()->config->getDirName() . DIRECTORY_SEPARATOR . 'structure.php';
    return $path;
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

    foreach ($this->domains[$domain] as $re => $meta) {
      if (preg_match('#^' . $re . '$#', $path, $args)) {
        // Удаляем первый параметр (всё выражение).
        array_shift($args);

        $params = empty($meta['params'])
          ? array()
          : explode('+', $meta['params']);

        if (count($args) <= count($params) and !empty($meta['published'])) {
          $result = array(
            'name' => $meta['name'],
            'page' => $meta,
            'args' => array(),
            );

          foreach ($args as $k => $v)
            $result['args'][$params[$k]] = intval($v);

          $result['page'] = array_merge($result['page'], $overrides);

          return $result;
        }
      }
    }

    return false;
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
        'version' => self::version,
        'widgets' => $this->widgets,
        'aliases' => $this->aliases,
        'domains' => $this->domains,
        'schema' => $this->schema,
        'access' => $this->access,
        'modules' => $this->modules,
        ));
  }

  public function drop()
  {
    if (file_exists($file = $this->getFileName()))
      unlink($file);
  }

  public function getModuleConf($name)
  {
    if (!$this->loaded)
      $this->load();

    return array_key_exists($name, $this->modules)
      ? $this->modules[$name]
      : array();
  }
}
