<?php

class NodeStub
{
  private $id = null;
  private $data = null;

  /**
   * Устанавливается при необходимости сохранить объект.
   */
  private $dirty = false;

  private $db = null;

  /**
   * Стэк, используется для вывода общего массива нод в XML.
   */
  private static $stack = array();

  private function __construct($id, PDO $db)
  {
    if ($id instanceof NodeStub)
      $id = $id->id;

    if (null !== $id and !is_numeric($id)) {
      $value = is_object($id)
        ? '*' . get_class($id)
        : $id;
      throw new InvalidArgumentException(t('Идентификатор ноды должен быть числовым (получено: %id).', array(
        '%id' => $value,
        )));
    }

    $this->id = $id;
    $this->db = $db;
  }

  /**
   * Создание новой ноды.
   */
  public static function create($id, PDO $db)
  {
    if ($id instanceof NodeStub)
      $id = $id->id;
    if (null === $id or !array_key_exists($id, self::$stack))
      return new NodeStub($id, $db);
    return self::$stack[$id];
  }

  /**
   * Обращение к свойствам объекта.  Подгружает и разворачивает
   * дополнительные данные по мере необходимости.
   */
  public final function __get($key)
  {
    if ('id' == $key)
      return $this->id;

    $this->makeSureFieldIsAvailable($key);

    $value = array_key_exists($key, $this->data)
      ? $this->data[$key]
      : null;

    if ('uid' == $key)
      return self::create($value, $this->db);

    return $value;
  }

  /**
   * Изменение свойств объекта.
   */
  public final function __set($key, $value)
  {
    if ('id' == $key) {
      if (null !== $this->id)
        throw new InvalidArgumentException(t('Идентификатор объекта нельзя изменить.'));
      if (!is_integer($value))
        throw new InvalidArgumentException(t('Идентификатор объекта должен быть числовым.'));
      $this->id = $value;
    }

    $this->makeSureFieldIsAvailable($key);

    switch ($key) {
    case 'published':
    case 'deleted':
    case 'parent_id':
      throw new InvalidArgumentException(t('Свойство %name нельзя изменять стандартными средствами, используйте специальные методы.', array(
        '%name' => $key,
        )));
    }

    if (!array_key_exists($key, $this->data) or $this->data[$key] !== $value) {
      $this->data[$key] = $value;
      $this->dirty = true;
    }
  }

  /**
   * Проверяет, установлено ли поле.
   */
  public final function __isset($key)
  {
    if ('id' == $key)
      return null !== $this->id;

    $this->makeSureFieldIsAvailable($key);
    return !empty($this->data[$key]);
  }

  /**
   * Подгружает данные, необходимые для доступа к полю.
   */
  private function makeSureFieldIsAvailable($fieldName)
  {
    if ('id' == $fieldName)
      return;

    if (null === $this->data)
      $this->retrieve();

    if (!$this->isBasicField($fieldName)) {
      if (array_key_exists('data', $this->data)) {
        $fields = empty($this->data['data'])
          ? array()
          : unserialize($this->data['data']);
        $this->data = array_merge($fields, $this->data);
        unset($this->data['data']);
      }
    }
  }

  /**
   * Возвращает объект в виде XML.
   */
  public final function getXML($em = 'node', $extraContent = null)
  {
    if (null !== $this->id) {
      $ckey = 'node:' . $this->id . ':xml';

      if (!is_array($data = mcms::cache($ckey))) {
        $data = array(
          'id' => $this->id,
          '#text' => null,
          );

        // Форсируем загрузку всех параметров.
        $this->makeSureFieldIsAvailable('this_field_never_exists');

        if (empty($this->data['class']))
          throw new RuntimeException(t('Не удалось определить тип ноды.'));

        $schema = Schema::load($this->data['class']);

        foreach ($this->data as $k => $v) {
          if ($v instanceof NodeStub)
            $data['#text'] .= $v->getXML($k);

          else {
            if (isset($schema[$k]))
              $v = $schema[$k]->format($v);

            if ($this->isBasicField($k))
              $data[$k] = $v;
            else
              $data['#text'] .= html::em($k, html::cdata($v));
          }
        }

        mcms::cache($ckey, $data);
      }
    }

    if (null !== $extraContent) {
      if (!array_key_exists('#text', $data))
        $data['#text'] = $extraContent;
      else
        $data['#text'] .= $extraContent;
    }

    return html::em($em, $data);
  }

  /**
   * Сохраняет объект в БД.
   */
  public function save()
  {
    if ($this->dirty) {
      $data = $this->pack();

      // Создание новой ноды.
      if (null === $this->id) {
        $fields = '`' . join('`, `', array_keys($data)) . '`';
        $params = substr(str_repeat('?,', count($data)), 0, -1);

        $sql = "INSERT INTO `node` ({$fields}) VALUES ({$params})";
        $sth = $this->db->prepare($sql);
        $sth->execute($data);
        $this->id = $this->db->lastInsertId();
      }

      // Обновление существующей ноды.
      else {
        // Сохраняем текущую версию в архиве.
        $fields = '`id`, `lang`, `class`, `left`, `right`, `uid`, `created`, `updated`, `name`, `data`';
        $sth = $this->db->prepare("INSERT INTO `node__archive` ({$fields}) SELECT {$fields} FROM `node` WHERE `id` = ?");
        $sth->execute($this->id);

        // Обновляем текущую версию.
        $pairs = array();
        foreach ($data as $k => $v)
          $pairs[] = "`{$k}` = ?";
        $data[] = $this->id;
        $sth = $this->db->prepare("UPDATE `node` SET " . join(', ', $pairs) . " WHERE `id` = ?");
        $sth->execute($data);
      }

      $this->dirty = false;
    }
  }

  /**
   * Удаление ноды.
   */
  public function delete()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка удалить новый объект'));
    $this->setDeleted(1);
  }

  /**
   * Восстановление ноды.
   */
  public function undelete()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка восстановить новый объект.'));
    $this->setDeleted(0);
  }

  private function setDeleted($value)
  {
    $sth = $this->db->prepare("UPDATE `node` SET `deleted` = ? WHERE `id` = ?");
    $sth->execute(array($value, $this->id));
  }

  /**
   * Публикация ноды.
   */
  public function publish()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка публикации несуществующей ноды.'));
    $this->setPublished(1);
  }

  /**
   * Сокрытие ноды.
   */
  public function unpublish()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка сокрытия несуществующей ноды.'));
    $this->setPublished(0);
  }

  private function setPublished($value)
  {
    $sth = $this->db->prepare("UPDATE `node` SET `published` = ? WHERE `id` = ?");
    $sth->execute(array($value, $this->id));
  }

  /**
   * Получение списка родителей.
   */
  public function getParents()
  {
    $result = array();

    if (null !== $this->id) {
      $sql = "SELECT `parent`.`id` as `id` "
        ."FROM `node` AS `self`, `node` AS `parent`, `node__rev` AS `rev` "
        ."WHERE `self`.`left` BETWEEN `parent`.`left` "
        ."AND `parent`.`right` AND `self`.`id` = ? AND `rev`.`rid` = `parent`.`rid` "
        ."ORDER BY `parent`.`left`";
      foreach ($this->db->getResultsV("id", $sql, array($this->id)) as $id)
        $result[] = self::create($id, $this->db);
    }

    return $result;
  }

  /**
   * Получение списка связанных объектов.
   */
  public function getLinked($class = null)
  {
    $result = array();

    if (null !== $this->id) {
      $params = array($this->id);
      $sql = "SELECT `nid` FROM `node__rel` WHERE `tid` = ? "
        . "AND `nid` NOT IN (SELECT `id` FROM `node` WHERE `deleted` = 0)";

      if (null !== $class) {
        $sql .= " AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = ?)";
        $params[] = $class;
      }

      foreach ($this->db->getResultsV("nid", $sql, $params) as $id)
        $result[] = self::create($id, $this->db);
    }

    return $result;
  }

  /**
   * Возвращает имя объекта.  TODO: вынести.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Упаковывает ноду для сохранения в БД.
   */
  private function pack()
  {
    $fields = $extra = array();

    if (null !== $this->id)
      $fields['id'] = $this->id;

    foreach ($this->data as $k => $v) {
      if ($this->isBasicField($k))
        $fields[$k] = $v;
      else
        $extra[$k] = $v;
    }

    $fields['data'] = serialize($extra);

    return $fields;
  }

  /**
   * Возвращает объект, управляющий нодой.
   */
  public function getManager()
  {
    if (null === $this->id)
      $class = 'Node';
    elseif (!class_exists($this->class . 'node'))
      $class = 'Node';
    return new $class($this);
  }

  /**
   * Проверяет, является ли поле стандартным.
   */
  private function isBasicField($fieldName)
  {
    switch ($fieldName) {
    case 'id':
    case 'parent_id':
    case 'name':
    case 'lang':
    case 'class':
    case 'left':
    case 'right':
    case 'uid':
    case 'created':
    case 'updated':
    case 'published':
    case 'deleted':
      return true;
    default:
      return false;
    }
  }

  private function retrieve()
  {
    $data = $this->db->getResults("SELECT `node`.`parent_id`, "
      . "`node`.`lang`, `node`.`class`, `node`.`left`, `node`.`right`, "
      . "`node`.`uid`, `node`.`created`, `node`.`updated`, "
      . "`node`.`published`, `node`.`deleted`, "
      . "`node__rev`.`name`, `node__rev`.`data` "
      . "FROM `node` "
      . "INNER JOIN `node__rev` ON `node__rev`.`rid` = `node`.`rid` "
      . "WHERE `node`.`id` = " . intval($this->id));

    $this->data = (null === $data)
      ? array()
      : $data[0];

    // Вытягиваем связанные объекты.
    $data = $this->db->getResultsKV("key", "nid", "SELECT `key`, `nid` FROM `node__rel` WHERE `tid` = ? AND `key` IS NOT NULL AND `nid` NOT IN (SELECT `id` FROM `node` WHERE `deleted` = 1)", array($this->id));
    foreach ($data as $k => $v)
      $this->data[$k] = self::create($v, $this->db);
  }

  /**
   * Добавление объекта в стэк.
   */
  public function push($em = 'node')
  {
    if (null === $this->id)
      return null;
    return $this->getXML($em);

    // Когда научимся нормально работать с контекстом, можно будет
    // сэкономить на размере, используя ссылки по ходу дерева и
    // один общий массив с объектами.
    /*
    if (!array_key_exists($this->id, self::$stack))
      self::$stack[$this->id] = $this;
    */
  }

  /**
   * Возврат стэка и очистка.
   */
  public static function getStack($em = 'nodes')
  {
    $result = '';

    if (0 == ($count = count(self::$stack)))
      return null;

    ksort(self::$stack);
    foreach (self::$stack as $node)
      $result .= $node->getXML();

    self::$stack = array();

    return html::em($em, array(
      'count' => $count,
      ), $result);
  }

  public function getObject()
  {
    return Node::create($this);
  }
}
