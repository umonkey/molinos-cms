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
  const version = 2;

  private static $instance = null;

  private $loaded = false;
  protected $access = array();

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
    if (!$this->loaded)
      $this->load();

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
        'access' => $this->access,
        ));
  }

  public function drop()
  {
    if (file_exists($file = $this->getFileName())) {
      Logger::log('removed ' . $file);
      unlink($file);
    }
  }
}
