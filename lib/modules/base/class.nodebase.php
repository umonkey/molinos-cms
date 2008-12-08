<?php
/**
 * Database related functions for Nodes.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Database related functions for Nodes.
 *
 * @package mod_base
 * @subpackage Core
 */
class NodeBase
{
  /**
   * Содержимое объекта.
   *
   * Содержимое этого массива грузится из БД и сохраняется.
   */
  protected $data = array();

  /**
   * Содержимое объекта до его модификации.
   */
  protected $olddata = array();

  /**
   * Прикреплённые файлы.
   *
   * Вынесены в отдельный массив чтобы не попадать в $data и не сохраняться в
   * БД.  @todo можно от него избавиться, т.к. теперь при сохранении $data
   * проверяется на наличие нод.
   */
  public $files = array();

  private $_links = array();

  /**
   * Конструктор.
   *
   * Создаёт пустой объект, заполняя его указанными данными.  Копирует поле
   * updated в created, если оно пусто.
   *
   * Доступ извне запрещён, т.к. ноды надо создавать через Node::create().
   *
   * @see Node::create()
   *
   * @param array $data свойства объекта.
   */
  protected function __construct(array $data = null)
  {
    if (null !== $data) {
      if (empty($data['created']) and !empty($data['updated']))
        $data['created'] = $data['updated'];
    }

    $this->data = $data;
    $this->olddata['id'] = $this->id;
  }

  // Проверяет наличие других объектов с таким именем.
  /**
   * Проверка уникальности.
   *
   * @param string $field имя поля, по которому проверяется уникальность.  Поле
   * должно быть базовым или должно быть проиндексировано.  Обычно используется
   * "name".
   *
   * @param string $message сообщение об ошибке при нарушении уникальности. По
   * умолчанию: "Такой объект уже существует".
   *
   * @param array $filter Дополнительные условия, накладываемые на проверяемые
   * объекты.  Например, можно указать "parent_id" для обеспечния уникальности в
   * рамках одной ветки — так работает проверка имени страницы, например.
   *
   * @return void
   */
  protected function checkUnique($field, $message = null, array $filter = array())
  {
    $filter['class'] = $this->class;
    $filter[$field] = $this->$field;

    if ($this->id)
      $filter['-id'] = $this->id;

    try {
      if (Node::count($filter))
        throw new DuplicateException($message ? $message : t('Такой объект уже существует.'));
    } catch (PDOException $e) { }
  }

  /**
   * Возвращает содержимое объекта в виде массива.
   *
   * Прикреплённые файлы возвращаются в виде массива с именем "files".
   *
   * @return array поля объекта + массив "files".
   */
  public function getRaw()
  {
    $tmp = $this->data;

    foreach ($tmp as $k => $v)
      if ($v instanceof Node)
        $tmp[$k] = $v->getRaw();

    foreach ($this->files as $k => $v)
      $tmp['files'][$k] = $v->getRaw();

    $tmp['_name'] = $this->getName();

    return $tmp;
  }

  /**
   * Получение идентификатора объекта.
   *
   * Позволяет упростить использование объектов в строках.  В частности это
   * удобно, когда не известно, является ли переменная объектом Node, или
   * числовым идентификатором.  Синтаксис strval($node) позволяет избежать
   * лишних проверок.
   *
   * @return string числовой идентификатор или пустая строка для новых объектов.
   */
  public final function __toString()
  {
    return strval($this->id);
  }

  // Читаем объект.
  /**
   * Загрузка объекта из БД.
   *
   * Если объектов, удовлетворяющих условию, несколько — кидает исключение
   * InvalidArgumentException (может быть подавлено параметром $first, см.
   * дальше).
   *
   * Действие метода аналогично выполнению Node::find() и возврату первого
   * элемента полученного списка.
   *
   * @param mixed $id числовой идентификатор или условие (см. NodeQueryBuilder).
   *
   * @param bool $first true — вернуть первый объект из множества.
   */
  public static function load($id, $first = false)
  {
    if (!is_array($id))
      $id = array('id' => $id);

    if (!empty($id['#cache']) and !empty($id['id']) and is_array($cached = mcms::cache('node:' . $id['id'])))
      return Node::create($cached['class'], $cached);

    $data = self::find($id);

    if (empty($data))
      throw new ObjectNotFoundException(array_key_exists('#error', $id) ? $id['#error'] : null);

    elseif (count($data) > 1 and !$first)
      throw new InvalidArgumentException("Выборка объекта по условию вернула "
        ."более одного объекта. Условие: ". var_export($id, true));

    $node = array_shift($data);

    if (!empty($id['#cache']))
      mcms::cache('node:' . $node->id, $node->getRaw());

    return $node;
  }

  /**
   * Поиск документов по условию.
   *
   * @param $query Условие в формате NodeQueryBuilder.  Может содержать
   * дополнительные ключи: #raw — возвращать массивы вместо объектов класса
   * Node; #recurse — уровень рекурсии при подгрузке объектов по
   * ссылкам (по умолчанию: 1).
   *
   * @param integer $limit Ограничение на количество объектов.
   *
   * @param integer $offset Количество пропускаемых объектов.
   *
   * @return array Массив объектов класса Node или массивов, см. $query.
   */
  public static function find(array $query, $limit = null, $offset = null)
  {
    $query['#limit'] = $limit;
    $query['#offset'] = $offset;

    if (!array_key_exists('#recurse', $query))
      $query['#recurse'] = 1;

    $sql = null;
    $params = array();
    $fetch_extra = true;
    $fetch_att = !array_key_exists('#files', $query)
      or !empty($query['#files']);

    // Список запрашиваемых полей.
    $fields = array('`node`.`id`', '`node`.`rid`',
      '`node`.`class`', '`node`.`parent_id`', '`node`.`uid`',
      '`node`.`created`', '`node`.`updated`', '`node`.`lang`',
      '`node`.`published`', '`node`.`deleted`', '`node`.`left`',
      '`node`.`right`', '`node__rev`.`name`', '`node__rev`.`data`');

    $qb = new NodeQueryBuilder($query);
    $qb->getSelectQuery($sql, $params, $fields);

    // Листалка.
    if (!empty($limit) and !empty($offset))
      $sql .= sprintf(" LIMIT %d, %d", $offset, $limit);
    elseif (!empty($limit))
      $sql .= sprintf(" LIMIT %d", $limit);

    if (!empty($query['class']))
      $sql .= ' -- Node::find(type='. join(',', (array)$query['class']) .')';
    elseif (!empty($query['id']) and is_numeric($query['id']))
      $sql .= ' -- Node::find('. $query['id'] .')';
    else
      $sql .= ' -- Node::find()';

    $data = self::dbRead($sql, $params, empty($query['#recurse'])
      ? 0 : intval($query['#recurse']));

    if (!empty($query['#raw']))
      foreach ($data as $k => $v)
        $data[$k] = $v->getRaw();

    return $data;
  }

  /**
   * Возвращает количество объектов, удовлетворяющих условию.
   *
   * @param array $query описание запроса, см. NodeQueryBuilder.
   *
   * @return integer количество объектов.
   */
  public static function count(array $query)
  {
    $sql = null;
    $params = array();

    $qb = new NodeQueryBuilder($query);
    $qb->getCountQuery($sql, $params);

    if (!empty($query['#debug']))
      mcms::debug($query, $sql, $params);

    return mcms::db()->getResult($sql . " -- Node::count()", $params);
  }

  /**
   * Сохранение текущего объекта в БД.
   *
   * После сохранения объекта вызывается метод hookNodeUpdate() интерфейса
   * iNodeHook всех классов, которые этот интерфейс реализуют.  Метод получает
   * два параметра: сохраняемый объект и тип операции — "create" или "update".
   *
   * После вызова iNodeHook происходит очистка устаревших ревизий (см.
   * purgeRevisions()).
   *
   * @return Node ссылка на себя, для построения цепочек.
   */
  public function save()
  {
    $isnew = empty($this->id);

    if (empty($this->created))
      $this->created = gmdate('Y-m-d H:i:s');

    if (empty($this->data['uid']) and $this->data['class'] != 'user' and $this->data['class'] != 'group')
      if (empty($this->data['anonymous']))
        $this->data['uid'] = mcms::user()->id;

    // FIXME: вынести детей из data в отдельную переменную.
    if (isset($this->data['children']))
      unset($this->data['children']);

    if (!$this->id and !array_key_exists('published', $this->data))
      $this->data['published'] = mcms::user()->hasAccess('p', $this->data['class']);

    /*
    // таки надо анонимные комментарии уметь оставлять
    if (empty($this->uid))
      $this->data['uid'] = mcms::session('uid');
    */

    $this->dbWrite();
    $this->saveLinks();

    mcms::flush();

    mcms::invoke('iNodeHook', 'hookNodeUpdate', array($this, $isnew ? 'create' : 'update'));

    $this->purgeRevisions();

    return $this;
  }

  /**
   * Сохранение связанных объектов.
   *
   * Проверяет все поля объекта и созаёт ссылки (через таблицу "node__rel") для
   * всех полей, значения которых являются нодами.
   *
   * @return void
   */
  private function saveLinks()
  {
    foreach ($this->getSchema() as $field => $ctl) {
      if (false !== ($value = $ctl->getLinkId($this))) {
        if (null === $value)
          $this->linkRemoveChild(null, $field);
        else
          $this->linkAddChild($value, $field);
      }
    }

    $this->flushLinks();
  }

  private function flushLinks()
  {
    $queries = array();

    foreach ($this->_links as $link) {
      $params = array();

      switch ($link['action']) {
      case 'delete':
        if ($link['scope'] == 'children') {
          $field = 'tid';
          $other = 'nid';
        } else {
          $field = 'nid';
          $other = 'tid';
        }

        $sql = "DELETE FROM node__rel WHERE {$field} = ?";
        $params[] = $this->id;

        if (null !== $link['class']) {
          $sql .= " AND {$other} IN (SELECT id FROM node WHERE class = ?)";
          $params[] = $link['class'];
        }

        break;

      case 'remove':
        $sql = "DELETE FROM `node__rel` WHERE";

        if (array_key_exists('child', $link)) {
          $f1 = 'tid';
          $f2 = 'nid';
          $f2v = $link['child'];
        } else {
          $f1 = 'nid';
          $f2 = 'tid';
          $f2v = $link['parent'];
        }

        $sql .= " `{$f1}` = ?";
        $params[] = $this->id;

        if (!empty($link['key'])) {
          $sql .= " AND `key` = ?";
          $params[] = $link['key'];
        } else {
          $sql .= " AND `{$f2}` = ?";
          $params[] = $f2v;
        }

        break;

      case 'add':
        $sql = "INSERT INTO node__rel (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order)";

        if (array_key_exists('child', $link)) {
          $params[':tid'] = $this->id;
          $params[':nid'] = $link['child'];
        } else {
          $params[':tid'] = $link['parent'];
          $params[':nid'] = $this->id;
        }

        if ($params[':key'] = $link['key'])
          $queries[] = array(
            'sql' => 'DELETE FROM `node__rel` WHERE `tid` = ? AND `key` = ?',
            'params' => array($this->id, $params[':key']),
            );

        $params[':order'] = self::getNextOrder($params[':tid']);

        break;
      }

      $queries[] = array(
        'sql' => $sql,
        'params' => $params,
        );
    }

    try {
      foreach ($queries as $idx => $q)
        mcms::db()->exec($q['sql'], $q['params']);
    } catch (PDOException $e) {
      mcms::debug($e->getMessage() . ' in query ' . $idx, $queries);
    }

    $this->_links = array();
  }

  /**
   * Удаление устаревших ревизий.
   *
   * Количество хранимых ревизий содержится в параметре archive_limit настроек
   * модуля.
   *
   * @return void
   */
  private function purgeRevisions()
  {
    if (0 !== ($limit = intval(mcms::modconf('node', 'archive_limit')))) {
      $victim = mcms::db()->getResult("SELECT `rid` FROM `node__rev` WHERE `rid` < :current ORDER BY `rid` DESC LIMIT {$limit}, 1", array(
        ':current' => $this->rid,
        ));
      mcms::db()->exec("DELETE FROM `node__rev` WHERE `nid` = :nid AND `rid` <= :rid", array(
        ':nid' => $this->id,
        ':rid' => $victim,
        ));
    }
  }

  /**
   * Модификация свойств объекта.
   *
   * При попытке изменения свойств class, left, right или published кидает
   * исключение InvalidArgumentException.  То же самое происходит при попытке
   * изменить parent_id для объектов, у которых уже есть идентификатор (id !=
   * null).
   * 
   * @param string $key имя свойства.
   *
   * @param string $val новое значение свойства.
   *
   * @return mixed установленное значение.
   */
  public function __set($key, $val)
  {
    if (in_array($key, array('class', 'left', 'right', 'published')))
      throw new InvalidArgumentException("node.{$key} is private");

    if ($key == 'parent_id' and !empty($this->data['id']))
      throw new InvalidArgumentException("node.{$key} is private");

    if ($key == 'id') {
      $this->data['id'] = null;
      $this->data['rid'] = null;
      return;
    }

    if (!array_key_exists($key, $this->olddata))
      $this->olddata[$key] = $this->__get($key);

    return $this->data[$key] = $val;
  }

  /**
   * Чтение свойств объекта.
   *
   * @param string $key имя свойства.
   *
   * @return mixed значение свойства или NULL, если свойство не установлено.
   */
  public function __get($key)
  {
    if (!is_array($this->data))
      return null;
    return array_key_exists($key, $this->data) ? $this->data[$key] : null;
  }

  /**
   * Проверка наличия свойства.
   *
   * @param string $key имя свойства.
   *
   * @return bool true, если свойство установлено, иначе false.
   */
  public function __isset($key)
  {
    return is_array($this->data) and array_key_exists($key, $this->data);
  }

  /**
   * Удаление свойства объекта.
   *
   * @param string $key имя удаляемого свойства.
   *
   * @return void
   */
  public function __unset($key)
  {
    if (is_array($this->data) and array_key_exists($key, $this->data))
      unset($this->data[$key]);
  }

  /**
   * Публикация объекта.
   *
   * Если объект не опубликован — устанавливает свойство published в true.
   * Если объект уже существует (id != null) — свойство published изменяется
   * также в БД, происходит очистка кэша и вызов метода hookNodeUpdate()
   * интерфейса iNodeHook всех классов, которые этот интерфейс реализуют.
   *
   * Если у пользователя нет прав на публикацию объектов такого типа — кидает
   * ForbiddenException.  Если объект уже опубликован — ничего не происходит.
   *
   * @see iNodeHook
   *
   * @return Node ссылка на себя.
   */
  public function publish($rev = null)
  {
    if (!$this->checkPermission('p'))
      throw new ForbiddenException(t('У вас нет прав на публикацию '
        .'этого объекта.'));

    // Документ уже опубликован.
    if (!$this->published or (null === $rev) or ($this->rid != $rev)) {
      $this->data['published'] = true;

      if (isset($this->id)) {
        mcms::db()->exec("UPDATE `node` SET `published` = 1, `rid` = :rid WHERE `id` = :id", array(':rid' => $rev ? $rev : $this->rid, ':id' => $this->id));

        // Даём другим модулям возможность обработать событие (например, mod_moderator).
        mcms::invoke('iNodeHook', 'hookNodeUpdate', array($this, 'publish'));

        mcms::flush();
      }
    }

    return $this;
  }

  /**
   * Сокрытиче объекта.
   *
   * Сбрасывает флаг публикации объекта.  Модифицирует его свойство published в
   * БД, вызывает метод hookNodeUpdate() интерфейса iNodeHook классов, которые
   * этот интерфейс реализуют.
   *
   * @see iNodeHook
   *
   * @return Node ссылка на себя.
   */
  public function unpublish()
  {
    if (!$this->published or !$this->id)
      return $this;

    // Скрываем документ.
    mcms::db()->exec("UPDATE `node` SET `published` = 0 WHERE `id` = :id", array(':id' => $this->id));
    $this->data['published'] = false;

    $user = mcms::user();

    // Даём другим модулям возможность обработать событие (например, mod_moderator).
    mcms::invoke('iNodeHook', 'hookNodeUpdate', array($this, 'unpublish'));

    mcms::flush();

    return $this;
  }

  /**
   * Клонирование объекта.
   *
   * Создаёт в БД полную копию текущего объекта, включая все его связи.
   * Работает только если объект уже сохранён; если у объекта ещё нет
   * собственного идентификатора — ничего не происходит.
   *
   * @return Node новый объект (или текущий, если клонирование не проводилось).
   *
   * @param integer $parent идентификатор нового родителя.  Позволяет прикрепить
   * клонированный объект к новому родителю.
   */
  public function duplicate($parent = null, $with_children = true)
  {
    if (null !== ($id = $this->id)) {
      $this->id = null;
      $this->data['published'] = false;
      $this->data['deleted'] = false;

      // Даём возможность прикрепить клон к новому родителю.
      if (null !== $parent)
        $this->data['parent_id'] = $parent;

      $this->save();

      $pdo = mcms::db();
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

        if (($this->right - $this->left) > 1) {
          $children = Node::find(array(
            'parent_id' => $id,
            ));

          foreach ($children as $c)
            $c->duplicate($this->id);
        }
      }

      mcms::flush();
    }

    return $this;
  }

  /**
   * Полное уничтожение объекта.
   *
   * Удаляет объект, ранее помещённый в "корзину" методом delete().  При
   * отсутствии прав на удаление таких объектов возниает исключение
   * ForbiddenException().
   *
   * Перед удалением объекта вызывается метод hookNodeDelete() интерфейса
   * iNodeHook всех классов, которые этот интерфейс реализуют, затем объект
   * навсегда удаляется из БД, со всеми дочерними объектами.
   *
   * @return void
   */
  public function erase()
  {
    if (!$this->deleted)
      throw new RuntimeException(t('Невозможно окончательно удалить объект, который не был помещён в корзину.'));

    if (!$this->checkPermission('d'))
      throw new ForbiddenException(t('У вас нет прав на удаление этого объекта.'));

    $pdo = mcms::db();

    $meta = $pdo->getResult("SELECT `left`, `right`, `right` - `left` + 1 AS `width` FROM `node` WHERE `id` = :id", array(':id' => $this->id));

    if (!empty($meta['right']) and !empty($meta['left'])) {
      $nids = $pdo->getResultsKV("id", "class", "SELECT `id`, `class` FROM `node` WHERE `left` >= :left AND `right` <= :right", array(':left' => $meta['left'], ':right' => $meta['right']));

      // Вызываем дополнительную обработку.
      try {
        mcms::invoke('iNodeHook', 'hookNodeDelete', array($this, 'erase'));
      } catch (Exception $e) {
      }

      $pdo->exec("DELETE FROM `node` WHERE `left` BETWEEN :left AND :right", array(':left' => $meta['left'], ':right' => $meta['right']));

      $order = $pdo->hasOrderedUpdates() ? ' ORDER BY `right` ASC' : '';
      $pdo->exec("UPDATE `node` SET `right` = `right` - :width WHERE `right` > :right". $order, $args = array(':width' => $meta['width'], ':right' => $meta['right']));

      $order = $pdo->hasOrderedUpdates() ? ' ORDER BY `left` ASC' : '';
      $pdo->exec("UPDATE `node` SET `left` = `left` - :width WHERE `left` > :right". $order, $args);
    }
    else {
      $pdo->exec("DELETE FROM `node` WHERE id = :id", array(':id' => $this->id));
    }

    mcms::flush();
  }

  /**
   * Создание нового объекта.
   *
   * Создаёт (в памяти) объект указанного типа с требуемым наполнением.
   *
   * Если поле uid созданного объекта пусто, в него копируется идентификатор
   * текущего пользователя.  Если объект новый (с пустым id) и не является
   * профилем пользователя (class=user), поле published заполняется в
   * соответствии с правом пользователя на публикацию создаваемого объекта.  То
   * есть, если создаётся статья, и пользователь имеет право статьи публиковать,
   * объект будет создан опубликованным.
   *
   * @param string $class Тип объекта.  Если существует класс с именем
   * $class.Node — создаётся он, иначе используется базовый класс Node.
   *
   * @param array $data содержимое (свойства) объекта.
   *
   * @return Node объект.
   */
  public static function create($class, $data = null)
  {
    if (!is_array($data) and !empty($data))
      mcms::debug($data);

    if (!is_string($class))
      throw new InvalidArgumentException(t('Тип создаваемого объекта должен быть строкой, а не «%type».', array('%type' => gettype($class))));

    $host = self::getClassName($class);

    if (!is_array($data))
      $data = array();

    $data['class'] = $class;
    if (!array_key_exists('id', $data))
      $data['id'] = null;
    if (!array_key_exists('parent_id', $data))
      $data['parent_id'] = null;
    if (!array_key_exists('lang', $data))
      $data['lang'] = 'ru';

    return new $host($data);
  }

  public static function getClassName($class, $default = 'Node')
  {
    if (!class_exists($host = ucfirst(strtolower($class)) . 'Node'))
      $host = $default;
    return $host;
  }

  /**
   * Удаление объекта.
   *
   * Объект отмечается как удалённый и исчезает изо всех списков (кроме тех, что
   * явным образом выводят удалённые объекты).  После удаления вызывается метод
   * hookNodeUpdate() интерфейса iNodeHook всех классов, которые этот интерфейс
   * реализуют.
   *
   * При отсутствии у пользователя прав на удаление объекта возникает исключение
   * ForbiddenException.  Если объект ещё не был сохранён (id=null), возникает
   * исключение RuntimeException() с соответствующим текстом.
   *
   * При успешном удалении выполняется очистка кэша.
   *
   * @see iNodeHook
   *
   * @see mcms::flush()
   *
   * @return Node ссылка на себя.
   */
  public function delete()
  {
    if ($this->id === null)
      throw new RuntimeException(t("Попытка удаления несохранённой ноды."));

    if (!$this->checkPermission('d'))
      throw new ForbiddenException(t("У вас нет прав на удаление объекта."));

    $this->data['deleted'] = true;
    $pdo = mcms::db()->exec("UPDATE `node` SET `deleted` = 1 WHERE id = :nid", array('nid' => $this->id));

    mcms::invoke('iNodeHook', 'hookNodeUpdate', array($this, 'delete'));

    mcms::flush();

    return $this;
  }

  /**
   * Восстановление удалённого объекта.
   *
   * Объект отмечается как неудалённый, затем вызывается метод hookNodeUpdate()
   * интерфейса iNodeHook всех классов, которые этот интерфейс реализуют.
   *
   * При отсутствии у пользователя прав на удаление объекта возникает исключение
   * ForbiddenException.  Если объект ещё не был сохранён (id=null), возникает
   * исключение RuntimeException() с соответствующим текстом.
   *
   * При успешном восстановлении выполняется очистка кэша.
   *
   * @see iNodeHook
   * @see mcms::flush()
   * 
   * @return Node ссылка на себя.
   */
  public function undelete()
  {
    if (empty($this->deleted))
      throw new RuntimeException(t("Попытка восстановления неудалённой ноды."));

    if ($this->id === null)
      throw new RuntimeException(t("Попытка удаления несохранённой ноды."));

    if (!$this->checkPermission('d'))
      throw new ForbiddenException(t("У вас нет прав на удаление объекта &laquo;%name&raquo;.", array('%name' => $this->name)));

    $this->deleted = false;
    $this->save();

    mcms::invoke('iNodeHook', 'hookNodeUpdate', array($this, 'restore'));

    mcms::flush();
  }

  /**
   * Загрузка дочерних объектов.
   *
   * Дочерние объекты загружаются в виде дерева (вложенных массивов) и
   * помещаются в свойство children.  Если дочерних объектов не было — свойство
   * будет пустым (null).
   *
   * @param string $class загрузить детей только этого типа.
   *
   * @return Node ссылка на себя.
   */
  public function loadChildren($class = null, $bare = false)
  {
    if (null === $class)
      $class = $this->class;

    if ((empty($this->left) or empty($this->right)) and !empty($this->id)) {
      $tmp = mcms::db()->getResults("SELECT `left`, `right` FROM `node` WHERE `id` = :id", array(':id' => $this->id));

      if (!empty($tmp[0])) {
        $this->data['left'] = $tmp[0]['left'];
        $this->data['right'] = $tmp[0]['right'];
      }
    }

    if (empty($this->left) or empty($this->right)) {
      $this->data['children'] = null;
    } else {
      // Загружаем детей в плоский список.
      $children = Node::find($filter = array(
        'class' => $class,
        'left' => array('>'. $this->left, '<'. $this->right),
        '#sort' => array(
          'left' => 'asc',
          ),
        '#recurse' => $bare ? 0 : 1,
        '#files' => $bare ? 0 : 1,
        ));

      // Превращаем плоский список в дерево.
      $this->data['children'] = $this->make_tree($children);
    }

    return $this;
  }

  // Формирует из плоского списка элементов дерево.
  // Список обрабатывается в один проход.
  private function make_tree(array $nodes)
  {
    // Здесь будем хранить ссылки на все элементы списка.
    $map = array($this->id => $this);

    // Перебираем все данные.
    foreach ($nodes as $k => $node) {
      // Родитель есть, добавляем к нему.
      if (array_key_exists($node->data['parent_id'], $map))
          $map[$node->data['parent_id']]->data['children'][] = &$nodes[$k];

      // Добавляем все элементы в список.
      $map[$node->data['id']] = &$nodes[$k];
    }

    return $map[$this->id]->children;
  }

  // Возвращает список дочерних объектов, опционально фильтруя его.
  /**
   * Возвращает список дочерних объектов.
   *
   * @param string $mode Режим работы.  Варианты: "nested" — вложенные массивы,
   * "flat" — плоский список со свойством "depth" у каждого элемента, "select" —
   * плоский список с отступами у названий (пригоден для использования в
   * выпадающих списках).
   *
   * @param array $options дополнительные параметры.
   *
   * @return array массив объектов.
   */
  public function getChildren($mode, array $options = array())
  {
    if ($mode != 'nested' and $mode != 'flat' and $mode != 'select')
      throw new InvalidArgumentException(t('Неверный параметр $mode для Node::getChildren(), допустимые варианты: nested, flat.'));

    if (!array_key_exists('children', $this->data))
      $this->loadChildren();

    $result = $this;

    self::filterChildren($result,
      (array_key_exists('enabled', $options) and is_array($options['enabled'])) ? $options['enabled'] : null,
      (array_key_exists('search', $options) and is_string($options['search'])) ? $options['search'] : null);

    if ($mode == 'flat') {
      $result = self::makeFlat($result);
    } elseif ($mode == 'select') {
      $data = array();

      foreach (self::makeFlat($result) as $k => $v)
        $data[$v['id']] = str_repeat('&nbsp;', 2 * $v['depth']) . $v['name'];

      $result = $data;
    } elseif ($mode == 'nested') {
      $result = self::makeNested($result);
    }

    return $result;
  }

  // Фильтрует список дочерних объектов, оставляя только необходимые.
  // Попутно удаляет неопубликованные и удалённые объекты.
  private static function filterChildren(Node &$tree, array $enabled = null, $search = null)
  {
    if (array_key_exists('children', $tree->data) and is_array($tree->data['children'])) {
      foreach ($tree->data['children'] as $k => $v) {
        if (!($check = self::filterChildren($tree->data['children'][$k], $enabled, $search))) {
          unset($tree->data['children'][$k]);
        }
      }
    }

    $ok = true;

    // Удалённые объекты всегда удаляем.
    if (!empty($tree->data['deleted']))
      $ok = false;

    if ($enabled !== null and !in_array($tree->data['id'], $enabled))
      $ok = false;

    if ($search !== null and mb_stristr($tree->data['name'], $search) !== false)
      $ok = false;

    $tree->data['#disabled'] = !$ok;

    return $ok or !empty($tree->data['children']);
  }

  // Превращает дерево в плоский список.
  private static function makeFlat(Node $root, $depth = 0)
  {
    $output = array();

    $em = array(
      '#disabled' => !empty($root->data['#disabled']),
      'depth' => $depth,
      );

    foreach ($root->getRaw() as $k => $v)
      if (!is_array($v))
        $em[$k] = $v;

    $output[] = $em;

    if (!empty($root->data['children'])) {
      foreach ($root->data['children'] as $branch) {
        foreach (self::makeFlat($branch, $depth + 1) as $em)
          $output[] = $em;
      }
    }

    return $output;
  }

  // Превращает дерево объектов во вложенны массив.
  private static function makeNested(Node $root)
  {
    $output = $root->getRaw();

    if (!empty($output['children'])) {
      foreach ($output['children'] as $k => $v) {
        $output['children'][$k] = self::makeNested($v);
      }
    }

    return $output;
  }

  // Возвращает родителей текущего объекта, опционально исключая текущий объект.
  /**
   * Получение списка родителей объекта.
   *
   * Если объект ещё не сохранён — возвращает список родителей родительского
   * объекта.  Если и родительский объект не известен — возвращает NULL.
   *
   * @param bool $current false, если текущий объект возвращать не нужно.
   *
   * @return mixed массив нод или NULL, если родители не известны.
   */
  public function getParents($current = true)
  {
    if (null === ($tmp = $this->id))
      $tmp = $this->parent_id;

    if (null === $tmp)
      return null;

    $sql = "SELECT `parent`.`id` as `id`, `parent`.`parent_id` as `parent_id`, "
      ."`parent`.`class` as `class`, `rev`.`name` as `name`, "
      ."`rev`.`data` as `data` "
      ."FROM `node` AS `self`, `node` AS `parent`, `node__rev` AS `rev` "
      ."WHERE `self`.`left` BETWEEN `parent`.`left` "
      ."AND `parent`.`right` AND `self`.`id` = {$tmp} AND `rev`.`rid` = `parent`.`rid` "
      ."ORDER BY `parent`.`left` -- NodeBase::getParents({$tmp})";

    $nodes = self::dbRead($sql);

    if (!$current and array_key_exists($this->id, $nodes))
      unset($nodes[$this->id]);

    return $nodes;
  }

  // Применяет к объекту шаблон.  Формат имени шаблона: префикс.имя.tpl, префикс можно
  // передать извне (по умолчанию используется "doc").  Если массив с данными пуст,
  // будут использоваться данные текущего объекта.
  /**
   * Применение шаблона к объекту.
   *
   * Все параметры передаются в bebop_render_object().
   *
   * @see bebop_render_object()
   *
   * @param string $prefix если пусто — использлуется "doc".
   *
   * @param string $theme не изменяется.
   *
   * @param array $data если пусто — используется содержимое объекта.
   *
   * @return mixed полученные от шаблона результат (или NULL).
   */
  public function render($prefix = null, $theme = null, array $data = null)
  {
    if (null === $data)
      $data = $this->data;
    if (null === $prefix)
      $prefix = 'doc';

    return bebop_render_object($prefix, $theme, $data);
  }

  // РАБОТА СО СВЯЗЯМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Node_links

  /**
   * Получение родительских связей объекта.
   *
   * Возвращает список объектов, которые привязаны к текущему в качестве
   * родителей (поле tid таблицы node__rel).
   *
   * @param string $class желаемый тип объектов.
   *
   * @param bool $idsonly true — возвращать только числовые идентификаторы,
   * false — возвращать полное содержимое соответствующих записей в node__rel.
   *
   * @return array массив, описывающих связи.
   */
  public function linkListParents($class = null, $idsonly = false)
  {
    $params = array(':nid' => $this->id);

    $sub = "SELECT `id` FROM `node` WHERE `deleted` = 0";
    if (null !== $class) {
      $sub .= " AND `class` = :class";
      $params[':class'] = $class;
    }

    $sql = "SELECT `tid`, `key` FROM `node__rel` `r` "
      ."WHERE `tid` IN ({$sub}) AND `nid` = :nid ORDER BY `key` ASC "
      ."-- linkListParents({$this->id}, {$class}, {$idsonly})";

    $pdo = mcms::db();

    if ($idsonly)
      return $pdo->getResultsV("tid", $sql, $params);
    else
      return $pdo->getResults($sql, $params);
  }

  /**
   * Получение дочерних связей объекта.
   *
   * Возвращает список объектов, которые привязаны к текущему в качестве
   * детей (поле nid таблицы node__rel).
   *
   * @param string $class желаемый тип объектов.
   *
   * @param bool $idsonly true — возвращать только числовые идентификаторы,
   * false — возвращать полное содержимое соответствующих записей в node__rel.
   *
   * @return array массив, описывающих связи.
   */
  public function linkListChildren($class = null, $idsonly = false)
  {
    $params = array(':tid' => $this->id);
    $sql = "SELECT `r`.`nid` as `nid`, `r`.`key` as `key` "
      ."FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`nid` "
      ."WHERE `n`.`deleted` = 0 AND `r`.`tid` = :tid";

    if (null !== $class) {
      $sql .= " AND `n`.`class` = :class";
      $params[':class'] = $class;
    }

    $sql .= sprintf(" ORDER BY `r`.`order` ASC -- linkListChildren(%u, %s, %d)",
      $this->id, $class ? $class : 'NULL', $idsonly);

    $pdo = mcms::db();

    if ($idsonly)
      return $pdo->getResultsV("nid", $sql, $params);
    else
      return $pdo->getResults($sql, $params);
  }

  private function linkAdd($tid, $nid, $key)
  {
    mcms::debug('DEPRECATED!', $tid, $nid, $key);

    if (null !== $key)
      mcms::db()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `key` = ?", array($tid, $key));
    elseif (null !== $nid)
      mcms::db()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `nid` = ?", array($tid, $nid));

    mcms::db()->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) "
      ."VALUES (?, ?, ?, ?)", array($tid, $nid, $key,
        self::getNextOrder($tid)));

    mcms::flush();

    return $this;
  }

  private function linkBreak($tid, $nid, $key)
  {
    mcms::debug('DEPRECATED!', $tid, $nid, $key);

    if (null !== $nid)
      mcms::db()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `nid` = ?", array($tid, $nid));

    elseif (null !== $key)
      mcms::db()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `key` = ?", array($tid, $key));

    else
      throw new InvalidArgumentException(t('Для удаления связи '
        .'между объектами надо указать либо id объекта, '
        .'либо имя поля, к которому он привязан.'));

    mcms::flush();

    return $this;
  }

  /**
   * Привязка к объекту.
   *
   * При необходимости текущий объект сохраняется (для получения id).
   *
   * @param integer $parent_id идентификатор объекта, к которому следует
   * привязаться.
   *
   * @param string $key Имя связи (имя поля в родительском объекте).
   *
   * @return Node $this
   */
  public function linkAddParent($parent_id, $key = null)
  {
    $this->_links[] = array(
      'action' => 'add',
      'parent' => $parent_id,
      'key' => $key,
      );
  }

  /**
   * Привязка объекта к текущему.
   *
   * При необходимости текущий объект сохраняется (для получения id).
   *
   * @param integer $parent_id идентификатор объекта, к которому следует
   * привязаться.
   *
   * @param string $key Имя связи (имя поля в родительском объекте).
   *
   * @return Node $this
   */
  public function linkAddChild($child_id, $key = null)
  {
    $this->_links[] = array(
      'action' => 'add',
      'child' => $child_id,
      'key' => $key,
      );
  }

  /**
   * Отвязывание от объекта.
   *
   * @param integer $parent_id идентификатор объекта, от которого нужно
   * отвязаться.
   *
   * @return Node $this
   */
  public function linkRemoveParent($parent_id)
  {
    if ($parent_id !== null and !is_numeric($parent_id))
      throw new InvalidArgumentException(t('Параметр parent_id для linkRemoveParent должен быть числом.'));

    $this->_links[] = array(
      'action' => 'remove',
      'parent' => $parent_id,
      'key' => $key,
      );

    return $this;
  }

  /**
   * Отвязывание объектов от текущего.
   *
   * @param integer $parent_id идентификатор объекта, от которого нужно
   * отвязаться.
   *
   * @return Node $this
   */
  public function linkRemoveChild($child_id = null, $key = null)
  {
    if ($child_id !== null and !is_numeric($child_id))
      throw new InvalidArgumentException(t('Параметр child_id для linkRemoveChild должен быть числом.'));

    $this->_links[] = array(
      'action' => 'remove',
      'child' => $child_id,
      'key' => $key,
      );

    return $this;
  }

  /**
   * Массовая привязка объектов.
   *
   * Устанавливает "логических родителей" для текущего объекта.  Используется, в
   * основном, для привязки документов к разделам.
   *
   * @todo разобраться, внятно задокумментировать, переработать.
   *
   * @param array $list идентификаторы разделов.
   *
   * @param string $class ограничение на тип родителя.
   *
   * @param array $available ???
   *
   * @return Node $this
   */
  public function linkSetParents(array $list, $class, array $available = null)
  {
    if (empty($class))
      throw new InvalidArgumentException(t('Не указан тип для linkSetParents().'));

    $this->_links[] = array(
      'action' => 'delete',
      'scope' => 'parents',
      'class' => $class,
      );

    foreach ($list as $item) {
      if (is_array($item))
        $this->_links[] = array(
          'action' => 'add',
          'parent' => $item['id'],
          'key' => empty($item['key']) ? null : $item['key'],
          );
      else
        $this->_links[] = array(
          'action' => 'add',
          'parent' => $item,
          'key' => null,
          );
    }

    return $this;
  }

  /**
   * Массовая привязка объектов.
   *
   * Устанавливает "логических детей" для текущего объекта.  Используется, в
   * основном, для привязки разделов к типам документов.
   *
   * @todo разобраться, внятно задокумментировать, переработать.
   *
   * @param array $list идентификаторы типов.
   *
   * @param string $class ограничение на тип объектов.
   *
   * @param array $available ???
   *
   * @return Node $this
   */
  public function linkSetChildren(array $list, $class)
  {
    if (empty($class))
      throw new InvalidArgumentException(t('Не указан тип для linkSetChildren().'));

    $this->_links[] = array(
      'action' => 'delete',
      'scope' => 'children',
      'class' => $class,
      );

    foreach ($list as $item) {
      if (is_array($item))
        $this->_links[] = array(
          'action' => 'add',
          'child' => $item['id'],
          'key' => empty($item['key']) ? null : $item['key'],
          );
      else
        $this->_links[] = array(
          'action' => 'add',
          'child' => $item,
          'key' => null,
          );
    }

    return $this;
  }

  private static function getNextOrder($tid)
  {
    return mcms::db()->getResult("SELECT MAX(`order`) FROM `node__rel` WHERE `tid` = :tid", array(':tid' => $tid)) + 1;
  }

  /**
   * Перемещение связи с объектом.
   *
   * Используется для "ручной сортировки" документов в разделе.
   *
   * @return Node $this
   */
  public function moveBefore($tid)
  {
    // Прописываем дефолтные порядки.
    $this->orderFix();

    $pdo = mcms::db();

    $he = $pdo->getResult("SELECT `nid`, `tid`, `order` "
      ."FROM `node__rel` WHERE `nid` >= :nid",
        array(':nid' => $tid));
    $me = $pdo->getResults("SELECT `nid`, `tid`, `order` "
      ."FROM `node__rel` WHERE `tid` = :tid AND `order` >= :order "
      ."ORDER BY `order` DESC",
        array(':tid' => $he['tid'], ':order' => $he['order']));

    $orderTo = $he['order'];

    foreach ($me as $k => $node)
      $pdo->exec('UPDATE `node__rel` SET `order` = ? WHERE `nid` = ?',
        array($node['order'] + 1, $node['nid']));

    $pdo->exec('UPDATE `node__rel` SET `order` = ? WHERE `nid` = ?',
      array($orderTo, $me[0]['nid']));

    mcms::flush();

    return $this;
  }

  /**
   * Перемещение связи с объектом.
   *
   * Используется для "ручной сортировки" документов в разделе.
   *
   * @return Node $this
   */
  public function moveAfter($tid)
  {
    // Прописываем дефолтные порядки.
    $this->orderFix();

    $pdo = mcms::db();
    $he = $pdo->getResult("SELECT `nid`, `tid`, `order` FROM `node__rel` "
      ."WHERE `nid` >= :nid", array(':nid' => $tid));
    $me = $pdo->getResults("SELECT `nid`, `tid`, `order` FROM `node__rel` "
      ."WHERE `tid` = :tid AND `order` <= :order ORDER BY `order` ASC",
      array(':tid' => $he['tid'], ':order' => $he['order']));

    $orderTo = $he['order'];

    foreach ($me as $k => $node)
      $pdo->exec('UPDATE `node__rel` SET `order` = :order WHERE `nid` = :nid',
        array(':nid' => $node['nid'], ':order' => $node['order'] - 1));

    $pdo->exec('UPDATE `node__rel` SET `order` = :order WHERE `nid` = :nid',
      array(':nid' => $me[0]['nid'], ':order' => $orderTo));

    mcms::flush();

    return $this;
  }

  // РАБОТА С ПРАВАМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Permissions

  /**
   * Получение прав на объект.
   *
   * Возвращает таблицу с правами на текущий объект.
   * @todo описать формат таблицы.
   *
   * @return array описание прав.
   */
  public function getAccess()
  {
    static $default = null;

    $pdo = mcms::db();

    // Формируем список групп.
    if ($default === null) {
      $default[0] = array('name' => 'Анонимные пользователи');

      $data = $pdo->getResultsKV('id', 'name', "SELECT `v`.`nid` AS `id`, "
        ."`v`.`name` as `name` FROM `node` `n` INNER JOIN `node__rev` `v` "
        ."ON `v`.`rid` = `n`.`rid` WHERE `n`.`class` = 'group' "
        ."AND `n`.`deleted` = 0 ORDER BY `v`.`name`");

      foreach ($data as $k => $v)
        $default[$k]['name'] = $v;
    }

    $data = $default;
    $gids = join(', ', array_keys($default));

    $acl = mcms::db()->getResultsK('id', "SELECT `a`.`uid` as `id`, "
      ."`a`.`c` as `c`, "
      ."`a`.`r` as `r`, "
      ."`a`.`u` as `u`, "
      ."`a`.`d` as `d`, "
      ."`a`.`p` as `p` "
      ."FROM `node__access` `a` "
      ."WHERE `a`.`nid` = ? AND `a`.`uid` IN ({$gids})",
      array($this->id));

    // Формируем таблицу с правами.
    foreach ($acl as $id => $perms) {
      $data[$id]['c'] = $perms['c'];
      $data[$id]['r'] = $perms['r'];
      $data[$id]['u'] = $perms['u'];
      $data[$id]['d'] = $perms['d'];
      $data[$id]['p'] = $perms['p'];
    }

    return $data;
  }

  /**
   * Устанавливает права на объект.
   *
   * После изменения прав сбрасывается кэш.
   *
   * @param array $perms нужные права.
   *
   * @param bool $reset true — сбросить старые, false — дополнить.
   *
   * @return Node $this
   */
  public function setAccess(array $perms, $reset = true)
  {
    $pdo = mcms::db();

    if ($reset)
      $pdo->exec("DELETE FROM `node__access` WHERE `nid` = :nid", array(':nid' => $this->id));

    foreach ($perms as $uid => $values) {
      $args = array(
        ':nid' => $this->id,
        ':c' => in_array('c', $values) ? 1 : 0,
        ':r' => in_array('r', $values) ? 1 : 0,
        ':u' => in_array('u', $values) ? 1 : 0,
        ':d' => in_array('d', $values) ? 1 : 0,
        ':p' => in_array('p', $values) ? 1 : 0,
        );

      if (is_numeric($uid)) {
        $args[':uid'] = $uid;
        $pdo->exec($sql = "REPLACE INTO `node__access` (`nid`, `uid`, "
          ."`c`, `r`, `u`, `d`, `p`) VALUES (:nid, :uid, :c, :r, :u, :d, :p)",
          $args);
      } else {
        $args[':name'] = $uid;
        $pdo->exec($sql = "REPLACE INTO `node__access` (`nid`, `uid`, "
          ."`c`, `r`, `u`, `d`, `p`) SELECT :nid, `n`.`id` as `id`, "
          .":c, :r, :u, :d, :p FROM `node` `n` INNER JOIN `node__rev` `v` "
          ."ON `v`.`rid` = `n`.`rid` WHERE `n`.`class` = 'group' "
          ."AND `n`.`deleted` = 0 AND `v`.`name` = :name", $args);
      }
    }

    mcms::flush();

    return $this;
  }

  public function checkPermission($perm)
  {
    if (empty($_SERVER['HTTP_HOST']))
      return true;

    if ($this->uid == mcms::user()->id)
      if (in_array($perm, Structure::getInstance()->getOwnDocAccess($this->class)))
        return true;

    return mcms::user()->hasAccess($perm, $this->class);
  }

  // Если есть ноды с пустым `order`, надо бы их починить

  private function orderFix()
  {
    $pdo = mcms::db();
    $pdo->exec("UPDATE `node__rel` SET `order` = `nid` WHERE `order` IS NULL AND `tid` = :tid AND `key` IS NULL", array(':tid' => $parent));
  }

  // ИЗМЕНЕНИЕ ПОРЯДКА ДОКУМЕНТОВ.

  /**
   * Перемещение объекта.
   *
   * Перемещается либо объект в дереве (если родитель не указан), либо связь с
   * объектом (если родитель указан).  При отсутствии прав на изменение объекта
   * кидает ForbiddenException.
   *
   * @param integer $parent идентификатор объекта, связь с которым нужно
   * переместить.
   *
   * @return bool успешность перемещения.
   */
  public function orderUp($parent = null)
  {
    mcms::user()->checkAccess('u', $this->class);

    if (null === $parent) {
      $tmp = new NodeMover(mcms::db());
      $tmp->moveUp($this->id);
    } elseif (null !== $this->id) {
      $pdo = mcms::db();

      // Прописываем дефолтные порядки.
      $this->orderFix();

      // Определяем ближайшее верхнее значение.
      $my = $pdo->getResult("SELECT `order` FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent, ':nid' => $this->id));
      $order = $pdo->getResult("SELECT MAX(`order`) FROM `node__rel` WHERE `tid` = :tid AND `order` < :order", array(':tid' => $parent, ':order' => $my));

      // Двигать некуда.
      if (null === $order)
        return false;

      // Сдвигаем всё вниз, под себя.
      $pdo->exec("UPDATE `node__rel` SET `order` = `order` + :delta WHERE `order` >= :order AND `tid` = :tid AND `nid` <> :nid ORDER BY `order` DESC",
        array(':tid' => $parent, ':nid' => $this->id, ':order' => $order, ':delta' => $my - $order + 1));

      // Теперь сдвигаем всё наверх, на прежнее место, чтобы не было дырок.
      $pdo->exec("UPDATE `node__rel` SET `order` = `order` - :delta WHERE `order` >= :order AND `tid` = :tid ORDER BY `order` ASC",
        array(':tid' => $parent, ':order' => $my, ':delta' => $my - $order));
    }

    mcms::flush();

    return true;
  }

  /**
   * Перемещение объекта.
   *
   * Перемещается либо объект в дереве (если родитель не указан), либо связь с
   * объектом (если родитель указан).  При отсутствии прав на изменение объекта
   * кидает ForbiddenException.
   *
   * @param integer $parent идентификатор объекта, связь с которым нужно
   * переместить.
   *
   * @return bool успешность перемещения.
   */
  public function orderDown($parent = null)
  {
    mcms::user()->checkAccess('u', $this->class);

    if (null === $parent) {
      $tmp = new NodeMover(mcms::db());
      $tmp->moveDown($this->id);
    } elseif (null !== $this->id) {
      $pdo = mcms::db();

      // Прописываем дефолтные порядки.
      $pdo->exec("UPDATE `node__rel` SET `order` = `nid` WHERE `order` IS NULL AND `tid` = :tid AND `key` IS NULL", array(':tid' => $parent));

      // Определяем ближайшее нижнее значение.
      $my = $pdo->getResult("SELECT `order` FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent, ':nid' => $this->id));
      $next = $pdo->getResult("SELECT MIN(`order`) FROM `node__rel` WHERE `tid` = :tid AND `order` > :order", array(':tid' => $parent, ':order' => $my));

      if (null === $next)
        return false;

      // Сдвигаем всё вниз.
      $pdo->exec("UPDATE `node__rel` SET `order` = `order` + :delta WHERE `tid` = :tid AND (`order` > :order OR `nid` = :nid) ORDER BY `order` DESC",
        array(':tid' => $parent, ':nid' => $this->id, ':delta' => $next - $my + 1, ':order' => $next));
    }

    mcms::flush();

    return true;
  }

  /**
   * Возвращает информацию о соседях.
   *
   * @param integer $parent идентификатор родителя (раздела), в рамках которого
   * определяются соседи.
   *
   * @param array $classes допустимые типы соседей.
   *
   * @return mixed массив с ключами "left" и "right" или NULL.
   */
  public function getNeighbors($parent, array $classes = null)
  {
    $pdo = mcms::db();

    if (null === ($order = $pdo->getResult("SELECT `order` FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent, ':nid' => $this->id))))
      return null;

    if (null === $classes)
      $filter = '';
    else
      $filter = " AND `n`.`class` IN ('". join("', '", $classes) ."')";

    $left = $pdo->getResult($sql1 = "SELECT `n`.`id` as `id` FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`nid` "
      ."WHERE `tid` = :tid{$filter} AND `n`.`deleted` = 0 AND `n`.`published` = 1 "
      ."AND `n`.`class` = :class "
      ."AND `r`.`order` < :order ORDER BY `r`.`order` DESC LIMIT 1",
      array(':tid' => $parent, ':order' => $order, ':class' => $this->class));

    $right = $pdo->getResult($sql2 = "SELECT `n`.`id` as `id` FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`nid` "
      ."WHERE `tid` = :tid{$filter} AND `n`.`deleted` = 0 AND `n`.`published` = 1 "
      ."AND `n`.`class` = :class "
      ."AND `r`.`order` > :order ORDER BY `r`.`order` ASC LIMIT 1",
      array(':tid' => $parent, ':order' => $order, ':class' => $this->class));

    return array(
      'left' => $left === null ? null : Node::load($left),
      'right' => $right === null ? null : Node::load($right),
      );
  }

  // РАБОТА С ФОРМАМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Forms
  /**
   * Получение формы редактирования объекта.
   *
   * При отсутствии прав на создание/редактирование объекта кидает
   * ForbiddenException.
   *
   * @param bool $simple Должна ли быть форма простой, или может содержать
   * вкладки для редактирования привязки к разделам итд?
   *
   * @return Control форма.
   */
  public function formGet($simple = false)
  {
    if (null !== $this->id and !$this->checkPermission('u')) {
      if (mcms::user()->id != $this->id)
        throw new ForbiddenException(t('У вас недостаточно прав для редактирования этого документа.'));
    } elseif (null === $this->id and !$this->checkPermission('c')) {
      throw new ForbiddenException(t('У вас недостаточно прав для создания такого документа.'));
    }

    $tabs = array();

    foreach ($this->getFormFields() as $name => $field) {
      if (!($group = trim($field->group)))
        $group = count($tabs)
          ? array_shift(array_keys($tabs))
          : 'Основные';

      if ($field instanceof AttachmentControl)
        $group = 'Файлы';

      if (!array_key_exists($group, $tabs))
        $tabs[$group] = new FieldSetControl(array(
          'name' => $group,
          'label' => $group,
          'tabable' => true,
          ));

      $field->value = $name;

      $tabs[$group]->addControl($field);
    }

    $form = new Form(array(
      'action' => $this->getFormAction(),
      'title' => $this->getFormTitle(),
      ));

    if (count($tabs) > 1)
      foreach ($tabs as $tab)
        $form->addControl($tab);
    else
      foreach (array_shift($tabs)->getChildren() as $ctl)
        $form->addControl($ctl);

    $form->addControl(new SubmitControl(array(
      'text' => $this->getFormSubmitText(),
      )));

    if (mcms::user()->id and mcms::user()->hasAccess('u', 'type') and $this->class != 'type') {
      try {
        $type = Node::load(array(
          'class' => 'type',
          'name' => $this->class,
          ));

        $form->hlink = '<span>' . l('?q=admin/content/edit/' . $type->id . '&destination=CURRENT', 'схема') . '</span>';
      } catch (Exception $e) { }
    }

    if ($this->parent_id and !isset($schema['parent_id']))
      $form->addControl(new HiddenControl(array(
        'value' => 'parent_id',
        'default' => $this->parent_id,
        )));

    return $form;
  }

  /**
   * Возвращает заголовок формы редактирования объекта.
   */
  public function getFormTitle()
  {
    try {
      $type = Node::load(array(
        'class' => 'type',
        'name' => $this->class,
        ));

      $name = mb_strtolower(mcms_plain($type->title));

      if (mcms::user()->hasAccess('u', 'type'))
        $name = l("?q=admin/content/edit/{$type->id}&destination=CURRENT", $name);

      $name = ' (' . $name . ')';
    } catch (ObjectNotFoundException $e) {
      $type = '';
    }

    return $this->id
      ? $this->getName() . $name
      : t('Добавление нового документа') . $name;
  }

  public function getFormSubmitText()
  {
    return t('Сохранить');
  }

  public function getFormAction()
  {
    $next = empty($_GET['destination'])
      ? $_SERVER['REQUEST_URI']
      : $_GET['destination'];

    return $this->id
      ? "?q=nodeapi.rpc&action=edit&node={$this->id}&destination=". urlencode($next)
      : "?q=nodeapi.rpc&action=create&type={$this->class}&destination=". urlencode($next);
  }

  /**
   * Возвращает контролы для формы.
   */
  public function getFormFields()
  {
    $schema = $this->getSchema();

    if (!mcms::user()->id and !$this->id and class_exists('CaptchaControl'))
      $schema['captcha'] = new CaptchaControl(array(
        'value' => 'captcha',
        ));

    return $schema;
  }

  /**
   * Обработка данных формы.
   *
   * Вызывается при получении от пользователя формы, для применения полученных
   * данных к текущему объекту.
   *
   * @param array $data полученные данные.
   *
   * @return Node $this
   */
  public function formProcess(array $data)
  {
    $schema = $this->getFormFields();

    foreach ($schema as $name => $field) {
      $value = array_key_exists($name, $data)
        ? $data[$name]
        : null;

      $field->set($value, $this, $data);
    }

    return $this;
  }

  /**
   * Проверка возможности опубликовать объект.
   *
   * @return bool true, если прав на публикацию хватает.
   */
  public function canPublish()
  {
    if (!mcms::user()->hasAccess('p',$this->class))
      return false;

    return !in_array($this->class, array('group', 'user', 'type', 'widget'));
  }

  // Сохранение объекта в БД.
  private function dbWrite()
  {
    $extra = $this->data;

    if (array_key_exists('_links', $extra))
      unset($extra['_links']);

    // Вытаскиваем данные, которые идут в поля таблиц.
    $node = $this->dbWriteExtract($extra, array(
      'id', 'lang', 'parent_id', 'class', 'left', 'right',
      'uid', 'created', 'published', 'deleted', '__want_id'));
    $node_rev = $this->dbWriteExtract($extra, array('name'));

    if (empty($node['created']))
      $node['created'] = strftime('%Y-%m-%d %H:%M:%S');

    // Выделяем место в иерархии, если это необходимо.
    $this->dbExpandParent($node);

    // Удаляем лишние поля.
    $this->dbWriteExtract($extra, array('rid', 'updated', 'files'), true);

    // Создание новой ноды.
    if (empty($node['id'])) {
      if (!empty($node['__want_id']))
        $node_id = $node['__want_id'];
      else
        $node_id = $this->dbGetNextId();

      mcms::db()->exec($sql = "INSERT INTO `node` (`id`, `lang`, `parent_id`, `class`, `left`, `right`, `uid`, `created`, `updated`, `published`, `deleted`) VALUES (:id, :lang, :parent_id, :class, :left, :right, :uid, :created, :updated, :published, :deleted)", $params = array(
        'id' => $node_id,
        'lang' => $node['lang'],
        'parent_id' => $node['parent_id'],
        'class' => $node['class'],
        'left' => $node['left'],
        'right' => $node['right'],
        'uid' => self::dbId($node['uid']),
        'created' => empty($node['created']) ? mcms::now() : $node['created'],
        'updated' => mcms::now(),
        'published' => empty($node['published']) ? 0 : 1,
        'deleted' => empty($node['deleted']) ? 0 : 1,
        ));

      $this->data['id'] = $node['id'] = $node_id;
    }

    // Обновление существующей ноды.
    else {
      mcms::db()->exec("UPDATE `node` SET `uid` = :uid, `created` = :created, `updated` = :updated, `published` = :published, `deleted` = :deleted WHERE `id` = :id AND `lang` = :lang", array(
        'uid' => self::dbId($node['uid']),
        'created' => $node['created'],
        'updated' => mcms::now(),
        'published' => empty($node['published']) ? 0 : 1,
        'deleted' => empty($node['deleted']) ? 0 : 1,
        'id' => $node['id'],
        'lang' => $node['lang'],
        ));
    }

    // Сохранение ревизии.
    mcms::db()->exec($sql = "INSERT INTO `node__rev` (`nid`, `uid`, `name`, `name_lc`, `created`, `data`) VALUES (:nid, :uid, :name, :name_lc, :created, :data)", $params = array(
      'nid' => $node['id'],
      'uid' => self::dbId($node['uid']),
      'name' => $node_rev['name'],
      'name_lc' => mb_strtolower($node_rev['name']),
      'created' => mcms::now(),
      'data' => empty($extra) ? null : self::dbSerialize($extra),
      ));
    $this->data['rid'] = mcms::db()->lastInsertId();

    if (empty($this->data['rid'])) {
      mcms::debug('Error saving revision', $this->data, $sql, $params);
      throw new RuntimeException(t('Не удалось получить номер сохранённой ревизии.'));
    }

    // Замена старой ревизии новой.
    mcms::db()->exec("UPDATE `node` SET `rid` = :rid WHERE `id` = :id AND `lang` = :lang", array(
      'rid' => $this->data['rid'],
      'id' => $node['id'],
      'lang' => $node['lang'],
      ));

    // При ошибке сохранения кидаем исключение, чтобы откатить транзакцию.
    if (empty($this->data['id']) or empty($this->data['rid']))
      throw new RuntimeException('При сохранении объекта не был получен '
        .'код ревизии.');

    $this->reindex();
  }

  // Вытаскиывает из $data значения полей, перечисленных в $fields.  Возвращает
  // их в виде массива, из исходного массива поля удаляются.  При $cleanup из
  // него также удаляются пустые значения (empty()).
  private function dbWriteExtract(array &$data, array $fields, $cleanup = false)
  {
    $tmp = array();

    foreach ($fields as $f) {
      if (array_key_exists($f, $data)) {
        $tmp[$f] = $data[$f];
        unset($data[$f]);
      } else {
        $tmp[$f] = null;
      }
    }

    if ($cleanup) {
      foreach ($data as $k => $v) {
        if (empty($v))
          unset($data[$k]);
      }
    }

    return $tmp;
  }

  // Если создаётся новый документ, и для него указан родитель — вписываем
  // в иерархию, в противном случае ничего не делаем.
  private function dbExpandParent(array &$node)
  {
    if (empty($node['id']) and !empty($node['parent_id'])) {
      $parent = array_shift(mcms::db()->getResults("SELECT `left`, `right` FROM `node` WHERE `id` = :parent AND `lang` = :lang",
        array(':parent' => $node['parent_id'], 'lang' => $node['lang'])));

      if (!empty($parent) and !empty($parent['left']) and !empty($parent['right']) and ($parent['left'] >= $parent['right']))
        throw new RuntimeException(t('Серьёзное нарушение целостности БД: границы ветки %id нарушены (left=%left ≥ right=%right).', array(
          '%id' => $node['parent_id'],
          '%left' => $parent['left'],
          '%right' => $parent['right'],
          )));

      // Родитель сам вне дерева — прописываем его.
      if (empty($parent['left']) or empty($parent['right'])) {
        $pos = $this->dbGetNextValue('node', 'right');

        $parent['left'] = $pos;
        $parent['right'] = $pos + 3;

        $node['left'] = $pos + 1;
        $node['right'] = $pos + 2;

        mcms::db()->exec("UPDATE `node` SET `left` = :left, `right` = :right WHERE `id` = :id AND `lang` = :lang", array(
          'left' => $parent['left'],
          'right' => $parent['right'],
          'id' => $node['parent_id'],
          'lang' => $node['lang'],
          ));
      }

      // Раздвигаем родителя.
      else {
        $node['left'] = $parent['right'];
        $node['right'] = $node['left'] + 1;

        $ou = mcms::db()->hasOrderedUpdates();

        $order = $ou ? ' ORDER BY `left` DESC' : '';
        mcms::db()->exec("UPDATE `node` SET `left` = `left` + 2 "
          ."WHERE `left` >= :pos". $order, array(':pos' => $node['left']));

        $order = $ou ? ' ORDER BY `right` DESC' : '';
        mcms::db()->exec("UPDATE `node` SET `right` = `right` + 2 "
          ."WHERE `right` >= :pos". $order, array(':pos' => $node['left']));
      }
    }
  }

  // Возвращает следующий доступный идентификатор для таблицы node.
  private function dbGetNextId()
  {
    mcms::db()->exec("INSERT INTO `node__seq` (`n`) VALUES (1)");
    $k = mcms::db()->lastInsertId();
    return $k;
  }

  // Возвращает следующий доступный идентификатор для таблицы.
  // FIXME: при большой конкуренции будут проблемы.
  private function dbGetNextValue($table, $field)
  {
    return mcms::db()->getResult("SELECT MAX(`{$field}`) FROM `{$table}`") + 1;
  }

  /**
   * Загрузка объектов из БД.
   *
   * @param string $sql Запрос для получения даных.
   *
   * @param array $params Параметры SQL запроса.
   *
   * @param integer $recurse Уровень рекурсии.
   *
   * @return array массив нод.
   */
  public static function dbRead($sql, array $params = null, $recurse = 0)
  {
    $tags = false;
    $nodes = array();

    foreach ($res = mcms::db()->getResults($sql, $params) as $row) {
      // Складывание массивов таким образом может привести к перетиранию
      // системных полей, но в нормальной ситуации такого быть не должно:
      // при сохранении мы удаляем всё системное перед сериализацией.
      // Зато такой подход быстрее ручного перебора.
      if (!empty($row['data']) and is_array($tmp = unserialize($row['data'])))
        $row = array_merge($row, $tmp);

      unset($row['data']);

      $nodes[$row['id']] = Node::create($row['class'], $row);

      if ('tag' == $row['class'])
        $tags = true;
    }

    // Подгружаем прилинкованые ноды.
    if ($recurse and !empty($nodes)) {
      $map = array();

      // FIXME: distinct надо убрать, но в Node::link* надо добавить
      // форсирование уникальности, т.к. в SQLite REPLACE INTO по
      // составным ключам, похоже, не работает.
      $sql = "SELECT DISTINCT tid, nid, `key` AS _key "
        ."FROM `node__rel` WHERE `nid` IN (SELECT `id` FROM `node` WHERE "
        ."`deleted` = 0) AND `tid` "
        ."IN (". join(', ', array_keys($nodes)) .")";

      // FIXME: для разделов загружаем ТОЛЬКО именованые ссылки,
      // иначе мы загрузим ВСЕ документы, привязанные к разделам,
      // что при среднем-большом объёме контента перестанет работать.
      if ($tags)
        $sql .= ' AND (`key` IS NOT NULL OR `tid` NOT IN '
          .'(SELECT `id` FROM `node` WHERE `class` = \'tag\' '
          .'AND `deleted` = 0 AND `published` = 1))';

      foreach (mcms::db()->getResults($sql) as $l)
          $map[$l['nid']][] = $l;

      if (!empty($map)) {
        $extras = Node::find(array(
          'id' => array_keys($map),
          '#recurse' => $recurse - 1,
          ));

        foreach ($map as $k => $v) {
          if (array_key_exists($k, $extras)) {
            foreach ($v as $link) {
              $extra = $extras[$link['nid']];

              if (null !== $link['_key'])
                $nodes[$link['tid']]->data[$link['_key']] = $extra;
              elseif ('file' == $extra->class)
                $nodes[$link['tid']]->files[] = $extra;
            }
          }
        }
      }
    }

    return $nodes;
  }

  /**
   * Обновление информации об объекте в индексе.
   *
   * Если есть индексированные поля — обовляет запись в индексной таблице.
   *
   * @return Node $this
   */
  public function reindex()
  {
    $fields = array('id');
    $params = array(':id' => $this->id);

    $schema = $this->getSchema();

    foreach ($schema->getIndexes() as $k) {
      if ($schema[$k]->indexed) {
        $fields[] = $k;
        $params[':' . $k] = $schema[$k]->getIndexValue($this->$k);
      }
    }

    if (count($fields) > 1) {
      $sql = "REPLACE INTO `node__idx_{$this->class}` (`". join('`, `', $fields) ."`) VALUES (". join(', ', array_keys($params)) .")";
      mcms::db()->exec($sql, $params);
    }

    return $this;
  }

  /**
   * Получение списка ревизий.
   *
   * @return array Массив с ключами: created, uid, username, active.
   */
  public function getRevisions()
  {
    $sql = 'SELECT `v`.`rid` AS `rid`, `v`.`created` AS `created`, '
      .'`v`.`uid` AS `uid`, `u`.`name` AS `username` '
      .'FROM `node__rev` `v` LEFT JOIN `node` `n` '
      .'ON `n`.`id` = `v`.`uid` LEFT JOIN `node__rev` `u` '
      .'ON `u`.`rid` = `n`.`rid` WHERE v.nid = ? '
      .'ORDER BY `v`.`created` DESC';

    $data = array();

    foreach (mcms::db()->getResults($sql, array($this->id)) as $row) {
      $data[$row['rid']] = array(
        'created' => $row['created'],
        'uid' => $row['uid'],
        'username' => $row['username'],
        'active' => $row['rid'] == $this->rid,
        );
    }

    return $data;
  }

  private static function dbId($value)
  {
    if (is_array($value))
      return $value['id'];
    elseif ($value instanceof Node)
      return $value->id;
    else
      return $value;
  }

  private static function dbSerialize(array $arr)
  {
    foreach ($arr as $k => $v)
      if ($v instanceof Node)
        $arr[$k] = $v->id;

    return serialize($arr);
  }

  protected function addFile($field, array $fileinfo, Node &$node = null)
  {
    if (UPLOAD_ERR_NO_FILE == $fileinfo['error']) {
      // Удаление ссылки на файл.
      if (!empty($fileinfo['unlink'])) {
        if (is_numeric($field)) {
          $node->linkRemoveChild($field);

          foreach ($node->files as $k => $v)
            if ($v->id == $field)
              unset($node->files[$k]);
        } else {
          $node->linkRemoveChild(null, $field);
          $node->$field = null;
        }
      }

      // Выбор из ахрива.
      elseif (!empty($fileinfo['id']))
        $node->$field = Node::load($fileinfo['id']);

      // Загрузка по FTP.
      elseif (!empty($fileinfo['ftp']))
        FileNode::getFilesFromFTP($fileinfo['ftp'], $node->id);

      elseif (!empty($fileinfo['deleted'])) {
        $node->linkRemoveChild($fileinfo['id']);
      }
    }

    elseif (UPLOAD_ERR_INI_SIZE == $fileinfo['error'])
      throw new ValidationException(t('Файл %name слишком большой; '
        .'максимальный размер файла: %size.', array(
          '%name' => $fileinfo['name'],
          '%size' => ini_get('upload_max_filesize'),
          )));

    // Загрузка архива.
    elseif (FileNode::isUnzipable($fileinfo))
      FileNode::unzip($fileinfo['tmp_name'], 'tmp/upload', $node ? $node->id : $node);

    else {
      $file = Node::create('file')->import($fileinfo)->save();

      // Добавляем файл в текущую ноду, чтобы
      // при её сохранении создалась связь.

      if (is_numeric($field))
        $node->files[] = $file;
      else
        $node->$field = $file;
    }
  }

  public final function getSchema()
  {
    return Schema::load($this->class);
  }

  public function formGetFields()
  {
    $schema = $this->getSchema();

    // Выбор родителя возможен только при создании.
    if ($this->id and isset($schema['parent_id']))
      unset($schema['parent_id']);

    return $schema;
  }

  public static function getDefaultSchema()
  {
    return array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Заголовок'),
        'required' => true,
        'recommended' => true,
        ),
      'created' => array(
        'type' => 'DateTimeControl',
        'label' => t('Дата добавления'),
        'required' => false,
        'recommended' => true,
        ),
      'section' => array(
        'type' => 'SectionControl',
        'label' => t('Раздел'),
        'required' => true,
        'recommended' => true,
        ),
      );
  }

  public function isNew()
  {
    return empty($this->olddata['id']);
  }
};
