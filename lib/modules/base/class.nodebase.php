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

    $tmp['displayName'] = $this->getName();

    return $tmp;
  }

  /**
   * Рендерит описание ноды в XML.
   */
  protected function getRealXML($em, array $data, $_content)
  {
    $ckey = 'node:' . $this->id . ':xml';

    if (!is_array($attrs = mcms::cache($ckey))) {
      $content = '';
      $attrs = array();
      $schema = $this->getSchema();

      foreach ($data as $k => $v) {
        // Стандартные поля делаем атрибутами.
        if (self::isInternalField($k))
          $attrs[$k] = $v;

        // Служебные поля игнорируем.
        elseif (in_array($k, array('left', 'right')))
          ;

        // Дальше идут субэлементы, пустые игнорируем.
        elseif (empty($v))
          continue;

        else {
          $formatted = isset($schema[$k])
            ? $schema[$k]->format($v)
            : false;

          $formatted = (empty($formatted) or $formatted == $v)
            ? null
            : html::em('html', html::cdata($formatted));

          // Ноды разворачиваем в субэлементы.
          if ($v instanceof Node)
            $content .= $v->getXML($k, $formatted);

          // Массивы — тоже (их тут вообще не должно быть).
          elseif (is_array($v)) {
            if (array_key_exists('class', $v))
              $content .= html::em($k, $v, $formatted);
          }

          // Всё остальное — cdata.
          else {
            $content .= html::em($k, null === $formatted ? html::cdata($v) : $formatted);
          }
        }
      }

      $attrs['displayName'] = $this->getName();
      $attrs['#text'] = $content . $_content;

      mcms::cache($ckey, $attrs);
    }

    return html::em($em, $attrs);
  }

  /**
   * Возвращает содержимое ноды в XML.
   */
  public function getXML($em = 'node', $_content = null)
  {
    return $this->getRealXML($em, $this->data, $_content);
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
      '`node`.`right`', '`node`.`name`', '`node`.`data`');
    $fields = array('`node`.`id`');

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

    $db = Context::last()->db;

    $data = array();
    foreach ($db->getResultsV("id", $sql, $params) as $nid)
      $data[] = NodeStub::create($nid, $db);

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

    return Context::last()->db->getResult($sql . " -- Node::count()", $params);
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
        $this->data['uid'] = Context::last()->user->id;

    // FIXME: вынести детей из data в отдельную переменную.
    if (isset($this->data['children']))
      unset($this->data['children']);

    if (!$this->id and !array_key_exists('published', $this->data))
      $this->data['published'] = Context::last()->user->hasAccess('p', $this->data['class']);

    /*
    // таки надо анонимные комментарии уметь оставлять
    if (empty($this->uid))
      $this->data['uid'] = mcms::session('uid');
    */

    $this->dbWrite();
    $this->saveLinks();

    mcms::flush();

    $this->dirty = false;
    mcms::invoke('iNodeHook', 'hookNodeUpdate', array($this, $isnew ? 'create' : 'update'));

    // Если обработчик изменил объект — сохраняем снова.
    if ($this->dirty) {
      unset($this->data['dirty']);
      $this->dbWrite();
    }

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
        $this->getDB()->exec($q['sql'], $q['params']);
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
        $this->getDB()->exec("UPDATE `node` SET `published` = 1, `rid` = :rid WHERE `id` = :id", array(':rid' => $rev ? $rev : $this->rid, ':id' => $this->id));

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
    $this->getDB()->exec("UPDATE `node` SET `published` = 0 WHERE `id` = :id", array(':id' => $this->id));
    $this->data['published'] = false;

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

    $pdo = $this->getDB();

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
    $this->getDB()->exec("UPDATE `node` SET `deleted` = 1 WHERE id = :nid", array('nid' => $this->id));

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
      $tmp = $this->getDB()->getResults("SELECT `left`, `right` FROM `node` WHERE `id` = :id", array(':id' => $this->id));

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

  private function linkAdd($tid, $nid, $key)
  {
    mcms::debug('DEPRECATED!', $tid, $nid, $key);

    if (null !== $key)
      $this->getDB()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `key` = ?", array($tid, $key));
    elseif (null !== $nid)
      $this->getDB()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `nid` = ?", array($tid, $nid));

    $this->getDB()->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) "
      ."VALUES (?, ?, ?, ?)", array($tid, $nid, $key,
        self::getNextOrder($tid)));

    mcms::flush();

    return $this;
  }

  private function linkBreak($tid, $nid, $key)
  {
    mcms::debug('DEPRECATED!', $tid, $nid, $key);

    if (null !== $nid)
      $this->getDB()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
        ."AND `nid` = ?", array($tid, $nid));

    elseif (null !== $key)
      $this->getDB()->exec("DELETE FROM `node__rel` WHERE `tid` = ? "
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
    return Context::last()->db->getResult("SELECT MAX(`order`) FROM `node__rel` WHERE `tid` = :tid", array(':tid' => $tid)) + 1;
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

    $pdo = $this->getDB();

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

    $pdo = $this->getDB();
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

  // Если есть ноды с пустым `order`, надо бы их починить

  private function orderFix()
  {
    $pdo = $this->getDB();
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
    Context::last()->user->checkAccess('u', $this->class);
    $db = $this->getDB();

    if (null === $parent) {
      $tmp = new NodeMover($db);
      $tmp->moveUp($this->id);
    } elseif (null !== $this->id) {
      // Прописываем дефолтные порядки.
      $this->orderFix();

      // Определяем ближайшее верхнее значение.
      $my = $db->getResult("SELECT `order` FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent, ':nid' => $this->id));
      $order = $db->getResult("SELECT MAX(`order`) FROM `node__rel` WHERE `tid` = :tid AND `order` < :order", array(':tid' => $parent, ':order' => $my));

      // Двигать некуда.
      if (null === $order)
        return false;

      // Сдвигаем всё вниз, под себя.
      $db->exec("UPDATE `node__rel` SET `order` = `order` + :delta WHERE `order` >= :order AND `tid` = :tid AND `nid` <> :nid ORDER BY `order` DESC",
        array(':tid' => $parent, ':nid' => $this->id, ':order' => $order, ':delta' => $my - $order + 1));

      // Теперь сдвигаем всё наверх, на прежнее место, чтобы не было дырок.
      $db->exec("UPDATE `node__rel` SET `order` = `order` - :delta WHERE `order` >= :order AND `tid` = :tid ORDER BY `order` ASC",
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
    Context::last()->user->checkAccess('u', $this->class);

    if (null === $parent) {
      $tmp = new NodeMover($this->getDB());
      $tmp->moveDown($this->id);
    } elseif (null !== $this->id) {
      $pdo = $this->getDB();

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
    $pdo = $this->getDB();

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
      $this->getDB()->exec($sql, $params);
    }

    return $this;
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

  public function canEditFields()
  {
    return true;
  }

  private static function isInternalField($name)
  {
    switch ($name) {
    case 'id':
    case 'parent_id':
    case 'rid':
    case 'class':
    case 'name':
    case 'lang':
    case 'left':
    case 'right':
    case 'created':
    case 'updated':
    case 'published':
    case 'deleted':
    case 'depth':
      return true;
    default:
      return false;
    }
  }
};
