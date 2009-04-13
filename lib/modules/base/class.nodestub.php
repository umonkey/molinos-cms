<?php

class NodeStub
{
  private $id = null;
  private $data = null;
  private $onsave = array();

  /**
   * Устанавливается при необходимости сохранить объект.
   */
  private $dirty = false;

  private $db = null;

  /**
   * Стэк, используется для вывода общего массива нод в XML.
   */
  private static $stack = array();

  /**
   * Кэш, помогает при повторной загрузке одного объекта.
   */
  private static $cache = array();

  private function __construct($id, PDO_Singleton $db = null, array $data = null)
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

    if (null !== $data) {
      if (array_key_exists('id', $data))
        unset($data['id']);
      if (array_key_exists('name_lc', $data))
        unset($data['name_lc']);
      if (array_key_exists('xml', $data))
        unset($data['xml']);
    }

    $this->id = $id;
    $this->db = $db;
    $this->data = $data;
  }

  /**
   * Создание новой ноды.
   */
  public static function create($id, PDO_Singleton $db = null, $data = null)
  {
    if (null !== $data and array_key_exists('id', $data))
      unset($data['id']);
    if ($id instanceof NodeStub)
      $id = $id->id;
    if (null !== $id)
      $id = intval($id);
    if (null === $id or !array_key_exists($id, self::$stack))
      return new NodeStub($id, $db, $data);
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

    if (is_array($value) and array_key_exists('id', $value))
      return self::create($value['id'], $this->db, $value);

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
      return $this->id = $value;
    }

    $this->makeSureFieldIsAvailable($key);

    if ('xml' == $key)
      throw new InvalidArgumentException(t('Свойство xml недоступно.'));

    switch ($key) {
    case 'published':
    case 'deleted':
    case 'parent_id':
      if (null !== $this->id)
        throw new InvalidArgumentException(t('Свойство %name нельзя изменять стандартными средствами, используйте специальные методы.', array(
          '%name' => $key,
          )));
    }

    if (null === $this->data)
      $this->data = array();

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

  public final function __unset($key)
  {
    $this->makeSureFieldIsAvailable($key);
    if (array_key_exists($key, $this->data)) {
      unset($this->data[$key]);
      $this->dirty = true;
    }
  }

  /**
   * Сообщение при обращении к несуществующему методу.
   */
  private final function __call($method, $args)
  {
    mcms::debug('bad method call', $method, $args);
    throw new RuntimeException(t('Метод %class::%method() не существует.', array(
      '%class' => get_class($this),
      '%method' => $method,
      )));
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

    if (!self::isBasicField($fieldName)) {
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
   * Возвращает список свойств.
   */
  public function getProperties()
  {
    $this->makeSureFieldIsAvailable('iAmAnIdiotIfIAddedSuchAField');
    return array_keys($this->data);
  }

  /**
   * Возвращает ключ в кэше для этого объекта.
   */
  private function getCacheKey()
  {
    return 'node:' . $this->id . ':xml';
  }

  /**
   * Удаляет ноду из кэша.
   */
  private function flush()
  {
    mcms::cache($this->getCacheKey(), false);
  }

  /**
   * Форсирует сохранение объекта.
   */
  public function touch()
  {
    $this->dirty = true;
    return $this;
  }

  /**
   * Заново подгружает связанные объекты.
   */
  public function refresh()
  {
    $rows = $this->getDB()->getResultsK("key", "SELECT `node__rel`.`key`, `node`.* FROM `node__rel` "
      . "INNER JOIN `node` ON `node`.`id` = `node__rel`.`nid` "
      . "WHERE `node`.`deleted` = 0 AND `node__rel`.`tid` = ? "
      . "AND `node__rel`.`key` IS NOT NULL", array($this->id));

    $old = $this;

    foreach ($rows as $field => $data)
      $this->$field = new NodeStub($data['id'], $this->getDB(), $data);

    $this->dirty = true;
    return $this;
  }

  /**
   * Возвращает объект в виде XML.
   */
  public final function getXML($em = 'node', $extraContent = null, $recurse = true)
  {
    $data = array();

    if (null !== $this->id) {
      if (!is_array($data = mcms::cache($ckey = $this->getCacheKey()))) {
        $data = array(
          'id' => $this->id,
          '#text' => null,
          );

        // Форсируем загрузку всех параметров.
        $this->makeSureFieldIsAvailable('this_field_never_exists');

        if (empty($this->data['class']))
          throw new RuntimeException(t('Не удалось определить тип ноды.'));

        $schema = Schema::load($this->getDB(), $this->data['class']);

        foreach ($this->getProperties() as $k) {
          if (empty($k) or 'xml' == $k)
            continue;

          $v = $this->$k;

          if (empty($v))
            continue;

          $wrap_cdata = true;
          $wrap_element = true;

          if ($v instanceof NodeStub or $v instanceof Node) {
            try {
              $fmt = isset($schema[$k])
                ? $schema[$k]->format($v)
                : null;
              $v = $v->getXML($k, html::em('html', html::cdata($fmt)));
              $wrap_cdata = false;
              $wrap_element = false;
            } catch (ObjectNotFoundException $e) {
              $v = null;
            }
          }

          elseif (isset($schema[$k])) {
            $v = $schema[$k]->format($v);
            $wrap_cdata = false;
          }

          elseif (is_array($v))
            $v = null;

          if (!empty($v)) {
            if (self::isBasicField($k))
              $data[$k] = $v;
            elseif (!empty($v)) {
              if ($wrap_cdata)
                $v = html::cdata($v);
              if ($wrap_element)
                $v = html::em($k, $v);
              $data['#text'] .= $v;
            }
          }
        }

        $data['#text'] .= $this->getObject()->getExtraXMLContent();

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
   * Возвращает дерево объектов в виде XML.
   * Кэш не используется, следует реализовывать во вне.
   */
  public final function getTreeXML($em = 'node', $children = 'children')
  {
    $data = array();

    if (null !== $this->id) {
      $data = array(
        'id' => $this->id,
        '#text' => null,
        );

      // Форсируем загрузку всех параметров.
      $this->makeSureFieldIsAvailable('this_field_never_exists');

      $schema = empty($this->data['class'])
        ? null
        : Schema::load($this->getDB(), $this->data['class']);

      foreach ($this->data as $k => $v) {
        if ($v instanceof NodeStub)
          ;

        else {
          if (isset($schema[$k]))
            $v = $schema[$k]->format($v);

          if (self::isBasicField($k))
            $data[$k] = $v;
          else {
            if (null !== ($cdata = html::cdata($v)))
              $data['#text'] .= html::em($k, html::cdata($v));
          }
        }
      }

      if ($this->left and $this->right) {
        $tmp = '';
        foreach ($ids = (array)$this->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `published` = 1 AND `deleted` = 0 AND `class` = ? AND `parent_id` = ? ORDER BY `left`", array($this->class, $this->id)) as $child_id)
          $tmp .= NodeStub::create($child_id, $this->db)->getTreeXML($em, $children);
        if (!empty($tmp))
          $data['#text'] .= html::em($children, $tmp);
      }
    }

    return html::em($em, $data);
  }

  /**
   * Сохраняет объект в БД.
   */
  public function save()
  {
    if (null === $this->db)
      throw new RuntimeException(t('Сохранение невозможно: не получен указатель на БД.'));

    if ($this->dirty) {
      $this->lang = 'ru';
      $this->updated = gmdate('Y-m-d H:i:s');
      if (empty($this->created))
        $this->created = $this->updated;

      $data = $this->pack();

      if (null === $this->id)
        $this->saveNew($data);
      else
        $this->saveOld($data);

      try {
        foreach ($this->onsave as $query) {
          list($sql, $params) = $query;
          $sth = $this->db->prepare(str_replace('%ID%', intval($this->id), $sql));
          $sth->execute($params);
        }
      } catch (PDOException $e) {
        // mcms::debug($sql, $params);
        throw $e;
      }

      $this->updateLinks();

      $this->onsave = array();
      $this->dirty = false;

      $this->flush();

      $this->updateXML();
      $this->checkSchema();
    }

    return $this;
  }

  /**
   * Сохраняет XML представление ноды в БД.
   */
  private function updateXML()
  {
    if ($this->id) {
      try {
        $this->getDB()->exec("UPDATE `node` SET `xml` = ? WHERE `id` = ?",
          array($this->getXML(), $this->id));
      } catch (PDOException $e) { }
    }
  }

  /**
   * Форсирует обновление схемы при изменении некоторых типов.
   * 
   * @return void
   */
  private function checkSchema()
  {
    switch ($this->class) {
      case 'type':
      case 'group':
      case 'domain':
      case 'widget':
      case 'field':
        Structure::getInstance()->drop();
        break;
    }
  }

  /**
   * Клонирование объекта.
   */
  public function duplicate($parent = null, $with_children = true)
  {
    if (null !== ($id = $this->id)) {
      $this->makeSureFieldIsAvailable('I am stupid if I added this field');

      $this->id = null;
      $this->data['published'] = false;
      $this->data['deleted'] = false;
      $this->data['created'] = null;

      // Даём возможность прикрепить клон к новому родителю.
      if (null !== $parent)
        $this->data['parent_id'] = $parent;

      $this->dirty = true;
      $this->save();

      $pdo = $this->getDB();
      $params = array(':new' => $this->id, ':old' => $id);

      if ($with_children) {
        // Копируем права.
        $pdo->exec("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`)"
          ."SELECT :new, `uid`, `c`, `r`, `u`, `d`, `p` FROM `node__access` WHERE `nid` = :old", $params);

        // Копируем связи с другими объектами.
        $pdo->exec("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) "
          ."SELECT :new, `nid`, `key` FROM `node__rel` WHERE `tid` = :old", $params);
        $pdo->exec("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) "
          ."SELECT `tid`, :new, `key` FROM `node__rel` WHERE `nid` = :old", $params);

        /*
        if (($this->right - $this->left) > 1) {
          $children = Node::find(array(
            'parent_id' => $id,
            ));

          foreach ($children as $c)
            $c->duplicate($this->id);
        }
        */
      }
    }

    return $this;
  }

  private function saveNew(array $data)
  {
    if (null !== $this->parent_id) {
      $position = $this->db->getResult("SELECT `right` FROM `node` WHERE `id` = ?", array($this->parent_id));
      $max = intval($this->db->getResult("SELECT MAX(`right`) FROM `node`"));

      // Превращаем простую ноду в родительску.
      if (null === $position) {
        $this->db->exec("UPDATE `node` SET `left` = ?, `right` = ? WHERE `id` = ?", array($max, $max + 4, $this->parent_id));
        $data['left'] = $this->data['left'] = $max + 1;
        $data['right'] = $this->data['right'] = $max + 2;
      }

      // Расширяем существующую ноду.
      else {
        $delta = $max - $position + 1;

        // mcms::debug($position, $max, $delta);

        // Вообще можно было бы обойтись сортированным обновлением, но не все серверы
        // это поддерживают, поэтому делаем в два захода: сначала выносим хвост за
        // пределы текущего пространства, затем — возвращаем на место + 2.
        $this->db->exec("UPDATE `node` SET `left` = `left` + ? WHERE `left` >= ?", array($delta + 2, $position));
        $this->db->exec("UPDATE `node` SET `right` = `right` + ? WHERE `right` >= ?", array($delta + 2, $position));

        $this->db->exec("UPDATE `node` SET `left` = `left` - ? WHERE `left` >= ?", array($delta, $position + 2));
        $this->db->exec("UPDATE `node` SET `right` = `right` - ? WHERE `right` >= ?", array($delta, $position + 2));

        $data['left'] = $this->data['left'] = $position;
        $data['right'] = $this->data['right'] = $position + 1;
      }
    }

    list($sql, $params) = sql::getInsert('node', $data);
    $sth = $this->db->prepare($sql);
    $sth->execute($params);
    $this->id = $this->db->lastInsertId();
  }

  private function saveOld(array $data)
  {
    // Обновляем текущую версию.
    list($sql, $params) = sql::getUpdate('node', $data, 'id');
    $sth = $this->db->prepare($sql);
    $sth->execute($params);
  }

  /**
   * Добавление запроса в пост-обработку.
   */
  public function onSave($sql, array $params = null)
  {
    $this->onsave[] = array($sql, $params);
    $this->dirty = true;
  }

  /**
   * Удаление ноды.
   */
  public function delete()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка удалить новый объект'));
    if ($this->left and $this->right) {
      $this->getDB()->exec("UPDATE `node` SET `deleted` = 1 WHERE `id` = ? OR (`left` >= ? AND `right` <= ?)", array(
        $this->id,
        $this->left,
        $this->right,
        ));
      $this->checkSchema();
      $this->flush();
    } else {
      $this->setDeleted(1);
    }
  }

  /**
   * Полное удаление, мимо корзины.
   */
  public function erase()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка удалить новый объект'));
    $this->getDB()->exec("DELETE FROM `node` WHERE `id` = ?", array($this->id));
    $this->id = null;
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
    $this->checkSchema();
    $this->flush();
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
    $this->makeSureFieldIsAvailable('published');
    $this->data['published'] = true;
    $this->checkSchema();
    $this->flush();
  }

  /**
   * Получение списка родителей.
   */
  public function getParents()
  {
    $result = array();

    if (null !== $this->id) {
      $sql = "SELECT `parent`.`id` as `id` "
        ."FROM `node` AS `self`, `node` AS `parent` "
        ."WHERE `self`.`left` BETWEEN `parent`.`left` "
        ."AND `parent`.`right` AND `self`.`id` = ? "
        ."ORDER BY `parent`.`left`";
      foreach ($this->db->getResultsV("id", $sql, array($this->id)) as $id)
        $result[] = self::create($id, $this->db);
    }

    return $result;
  }

  /**
   * Получение списка связанных объектов.
   */
  public function getLinked($class = null, $ids = false)
  {
    $result = array();

    if (null !== $this->id) {
      $params = array($this->id);
      $sql = "SELECT `nid` FROM `node__rel` WHERE `tid` = ?";

      if (null !== $class) {
        $sql .= " AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0)";
        $params[] = $class;
      } else {
        $sql .= " AND `nid` NOT IN (SELECT `id` FROM `node` WHERE `deleted` = 1)";
      }

      foreach ((array)$this->db->getResultsV("nid", $sql, $params) as $id)
        $result[] = $ids
          ? $id
          : self::create($id, $this->db);
    }

    return $result;
  }

  /**
   * Получение списка объектов, к которым привязан текущий.
   */
  public function getLinkedTo($class = null, $ids = false)
  {
    $result = array();

    if (null !== $this->id) {
      $params = array($this->id);
      $sql = "SELECT `tid` FROM `node__rel` WHERE `nid` = ?";

      if (null !== $class) {
        $sql .= " AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0)";
        $params[] = $class;
      } else {
        $sql .= ' AND `tid` NOT IN (SELECT `id` FROM `node` WHERE `deleted` = 1)';
      }

      foreach ((array)$this->db->getResultsV("tid", $sql, $params) as $id)
        $result[] = $ids
          ? $id
          : self::create($id, $this->db);
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

    $this->makeSureFieldIsAvailable('never again');

    foreach ($this->data as $k => $v) {
      if (($v instanceof NodeStub) or ($v instanceof Node)) {
        if (null === $v->id)
          $v->save();
        // Запрещаем ссылки на себя.
        if ($this->id == $v->id)
          continue;
        $this->onSave("DELETE FROM `node__rel` WHERE `tid` = %ID% AND `key` = ?", array($k));
        $this->onSave("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) VALUES (%ID%, ?, ?)", array($v->id, $k));
        $extra[$k] = $v->serialize();
      } elseif (is_array($v) and !empty($v['id']) and $v['id'] == $this->id) {
        // Запрещаем ссылки на себя.
      } elseif (self::isBasicField($k)) {
        $fields[$k] = $v;
      } elseif ('xml' == $k) {
      } elseif (!empty($v)) {
        $extra[$k] = $v;
      }
    }

    $fields['data'] = serialize($extra);
    $fields['name_lc'] = self::getSortName($this->name);

    return $fields;
  }

  public static function getSortName($name)
  {
    $sortName = mb_strtolower($name);
    if (substr($sortName, 0, 4) == 'the ')
      $sortName = ltrim(substr($sortName, 4)) . ', the';
    elseif (substr($sortName, 0, 2) == 'a ')
      $sortName = ltrim(substr($sortName, 2)) . ', a';
    $sortName = preg_replace('/[\[\]]/', '', $sortName);
    return $sortName;
  }

  /**
   * Проверяет, является ли поле стандартным.
   */
  public static function isBasicField($fieldName)
  {
    switch ($fieldName) {
    case 'id':
    case 'parent_id':
    case 'name':
    case 'name_lc':
    case 'lang':
    case 'class':
    case 'left':
    case 'right':
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
    if (null === $this->db or null === $this->id)
      return $this->data = array();

    if (array_key_exists($this->id, self::$cache))
      $this->data = self::$cache[$this->id];

    else {
      if (null === ($this->data = $this->db->fetch("SELECT * FROM `node` WHERE `id` = ?", array($this->id))))
        throw new ObjectNotFoundException(t('Объект с номером %id не существует.', array(
          '%id' => intval($this->id),
          )));

      unset($this->data['id']);
      unset($this->data['name_lc']);
      unset($this->data['xml']);
      self::$cache[$this->id] = $this->data;

      mcms::flog('retrieved node ' . $this->id . ' (' . $this->data['class'] . ')');
    }
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

  public static function getClassName($class, $default = 'Node')
  {
    if (!class_exists($host = ucfirst(strtolower($class)) . 'Node'))
      $host = $default;
    return $host;
  }

  public function getObject()
  {
    $className = self::getClassName($this->class);
    return new $className($this);
  }

  /**
   * Заглушка для сериализаторов.
   */
  public function __sleep()
  {
    return array('id', 'data');
  }

  /**
   * Загружает дочерние ноды.
   */
  public static function getChildrenOf(PDO_Singleton $db, $class, $parent_id = null)
  {
    $result = array();

    if (null === $parent_id)
      $ids = $db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `parent_id` IS NULL ORDER BY `left`", array($class));
    else
      $ids = $db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `parent_id` = ? ORDER BY `left`", array($class, $parent_id));

    foreach ((array)$ids as $id)
      $result[] = NodeStub::create($id, $db);

    return $result;
  }

  /**
   * Загружает объект по имени.
   */
  public static function loadByName(PDO_Singleton $db, $name, $class)
  {
    $id = $db->getResult("SELECT `id` FROM `node` WHERE `class` = ? AND `name` = ? AND `deleted` = 0", array($class, $name));
    if (null === $id)
      throw new ObjectNotFoundException();
    else
      return NodeStub::create($id, $db);
  }

  /**
   * Возвращает ссылку на БД.
   */
  public function getDB()
  {
    return $this->db;
  }

  public function linkTo($parent_id)
  {
    $this->onSave('INSERT INTO `node__rel` (`tid`, `nid`) VALUES (?, %ID%)', array($parent_id));
  }

  public function serialize()
  {
    $this->makeSureFieldIsAvailable('no mercy');
    $data = array('id' => $this->id);

    foreach ($this->data as $k => $v) {
      if ($v instanceof NodeStub) {
        if ($v->id == $this->id)
          continue;
        else
          $data[$k] = $v->serialize();
      } elseif (empty($v)) {
        continue;
      } elseif (is_array($v) and !empty($v['id']) and $v['id'] == $this->id) {
        continue;
      }
      $data[$k] = $v;
    }

    return $data;
  }

  /**
   * Обновление ссылок на себя во всех объектах.
   */
  private function updateLinks()
  {
    $data = $this->serialize();

    $sel = $this->db->prepare("SELECT `tid`, `key`, `data` FROM `node__rel` "
      . "INNER JOIN `node` ON `node`.`id` = `node__rel`.`tid` "
      . "WHERE `tid` <> ? AND `nid` = ? AND `key` IS NOT NULL");
    $sel->execute(array($this->id, $this->id));

    $upd = $this->db->prepare("UPDATE `node` SET `data` = ? WHERE `id` = ?");

    while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
      $tmp = (array)@unserialize($row['data']);
      $tmp[$row['key']] = $data;

      $upd->execute(array(serialize($tmp), $row['tid']));
    }
  }
}
