<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class NodeBase implements iFormObject
{
  protected $data = array();

  // Сюда складываем загруженные ноды.
  static private $cache = array();

  // Номер ревизии, в который принудительно сохраняемся.
  protected $forcedrev = null;

  public function getRaw()
  {
    $tmp = $this->data;
    if (array_key_exists('html', $tmp))
      unset($tmp['html']);
    return $tmp;
  }

  // Достаёт объект из кэша.
  private static function getCached($id)
  {
    $result = null;

    if (null !== ($mode = mcms::config('cache_documents'))) {
      if ($mode != 'local') {
        $key = 'node:'. $id;
        $result = mcms::cache($key);
      } elseif ($mode == 'local') {
        $result = array_key_exists($id, self::$cache)
          ? self::$cache[$id]
          : null;
      }
    }

    return (is_array($result) or is_object($result)) ? $result : null;
  }

  // Кладёт в кэш.
  private static function setCached($id, $data)
  {
    if (null !== ($mode = mcms::config('cache_documents'))) {
      if ($mode != 'local') {
        $key = 'node:'. $id;
        mcms::cache($key, $data);
      } elseif ($mode == 'local') {
        self::$cache[$id] = $data;
      }
    }
  }

  // Читаем объект.
  public static function load($id)
  {
    if (!is_array($id))
      $id = array('id' => $id);

    $data = self::find($id);

    if (empty($data))
      throw new ObjectNotFoundException();

    elseif (count($data) > 1)
      throw new InvalidArgumentException("Выборка объекта по условию вернула более одного объекта. Условие: ". var_export($id, true));

    $node = array_shift($data);

    self::setCached($node->id, $node);

    return $node;
  }

  // Поиск документов по критерию.
  public static function find(array $query, $limit = null, $offset = null)
  {
    $sql = null;
    $params = array();
    $fetch_extra = true;
    $fetch_att = !array_key_exists('#files', $query) or !empty($query['#files']);

    // Список запрашиваемых полей.
    $fields = array('`node`.`id`', '`node`.`rid`', '`node`.`code`', '`node`.`class`', '`node`.`parent_id`', '`node`.`uid`', '`node`.`created`', '`node`.`updated`', '`node`.`lang`', '`node`.`published`', '`node`.`deleted`', '`node`.`left`', '`node`.`right`', '`node__rev`.`name`', '`node__rev`.`data`');

    $qb = new NodeQueryBuilder($query);
    $qb->getSelectQuery($sql, $params, $fields);

    // Если у нас получился только один класс -- запрос оптимизирован и содержит
    // все необходимые поля, запрашивать их отдельно не надо.
    if (null !== $qb->getClassName())
      $fetch_extra = false;

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

    $sth = mcms::db()->exec($sql, $params);

    $data = array();
    $count = 0;

    foreach (Tagger::getInstance()->getChildrenData($sth, $fetch_extra, $fetch_att) as $k => $v) {
      $count++;
      $data[$k] = Node::create($v['class'], $v);
      self::setCached($k, $v);
    }

    return $data;
  }

  // Возвращает количество документов, удовлетворяющих условию.
  public static function count(array $query)
  {
    $sql = null;
    $params = array();

    $qb = new NodeQueryBuilder($query);
    $qb->getCountQuery($sql, $params);

    return mcms::db()->getResult($sql . " -- Node::count()", $params);
  }

  // Сохранение объекта.
  public function save($clear = false, $forcedrev = null)
  {
    // Здесь хранятся идентификаторы создаваемых документов.
    static $mydocs = array();

    if ($isnew = (null === $this->id)) {
      if (!$this->checkPermission('c'))
        throw new ForbiddenException(t('Вы не можете создавать документы такого типа.'));
    } elseif (!in_array($this->id, $mydocs) and !$this->checkPermission('u')) {
      throw new ForbiddenException(t('Вы не можете изменять этот документ.'));
    }

    $tg = Tagger::getInstance();

    if ($forcedrev !== null)
      $this->forcedrev = $forcedrev;

    if ($clear)
      $this->data = $tg->clean($this->data);

    if (empty($this->data['created']))
      $this->data['created'] = 'UTC_TIMESTAMP()';

    if (!array_key_exists('published', $this->data))
      $this->data['published'] = 0;

    if ($this->id === null and null === $this->uid)
      $this->data['uid'] = mcms::user()->getUid();

    // Рендерим документ.
    $this->data['html'] = $this->render();

    $tg->nodeSave($this->data, $this->forcedrev);

    // Сохраняем номер ревизии для последующего повторного использования.
    if (array_key_exists('rid', $this->data))
      $this->forcedrev = $this->data['rid'];

    if ($isnew)
      $mydocs[] = $this->id;

    mcms::flush();

    if ($isnew)
      $this->sendNotification();

    $this->purgeRevisions();
  }

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

  private function sendNotification()
  {   
    $schema = TypeNode::getSchema($this->class);
          
    if (!empty($schema['notificationrecipients'])) {
      $host = mcms::config('basedomain');

      $body = '<p>'. t('Создан новый документ типа "%type":', array('%type' => $schema['title'])) .'</p>';
      $body .= '<dl>';

      foreach ($schema['fields'] as $k => $v) {
        if (!empty($this->data[$k])) {
          $body .= '<dt>'. htmlspecialchars($v['label']) .':</dt>';
          $body .= '<dd>'. htmlspecialchars($this->data[$k]) .'</dd>';
        }
      }

      $body .= '</dl>';
      $body .= '<p>'. t('<a href=\'@link\'>Подробности</a>', array(
        '@link' => 'http://'. $host .'/admin/node/'. $this->id .'/edit/',
        )) .'</p>';

      BebopMimeMail::send(null, $schema['notificationrecipients'], 'Новый документ на сайте '. $host, $body);
    }
  }

  public function __set($key, $val)
  {
    if (in_array($key, array('class', 'left', 'right', 'published')))
      throw new InvalidArgumentException("node.{$key} is private");

    if ('uid' == $key and !empty($this->data['uid']) and $this->data['uid'] != mcms::user()->getUid())
      throw new InvalidArgumentException(t('Нельзя изменить автора чужого документа.'));

    if ($key == 'parent_id' and !empty($this->data['id']))
      throw new InvalidArgumentException("node.{$key} is private");

    if ($key == 'id') {
      $this->data['id'] = null;
      $this->data['rid'] = null;
      $this->data['code'] = null;
      return;
    }

    /*
    if ($key != 'parent_id' and $key != 'deleted' and $key != 'uid') {
      $schema = TypeNode::getSchema($this->class);

      if (empty($schema['fields'][$key]) and !bebop_skip_checks())
        throw new InvalidArgumentException("there is no {$key} property in {$this->data['class']}");
    }
    */

    $this->data[$key] = $val;
  }

  public function __get($key)
  {
    if (!is_array($this->data))
      return null;
    return array_key_exists($key, $this->data) ? $this->data[$key] : null;
  }

  public function __isset($key)
  {
    return !empty($this->data[$key]);
  }

  // Публикация ноды.  Логика:
  // 1. Если у пользователя нет прав на публикацию -- отправляет запрос модератору.
  // 2. Если указана конкретная ревизия -- она публикуется, в противном случае
  //    публикуется последняя существующая ревизия документа.
  //
  // Возвращает true, если нода успешно опубликована, false если нет.
  public function publish($rev = null)
  {
    $user = mcms::user();

    // Публикатор.
    if ($user->hasGroup('Publishers') or ($this->class == 'moduleinfo' and $user->hasGroup('Developers'))) {
      $this->data['published'] = true;

      $pdo = mcms::db();

      if ($rev === null)
        $rev = $pdo->getResult("SELECT MAX(`rid`) FROM `node__rev` WHERE `nid` = :nid", array('nid' => $this->id));

      $pdo->exec("UPDATE `node` SET `published` = :published, `rid` = :rid WHERE `id` = :nid",
        array('rid' => $rev, 'nid' => $this->id, 'published' => $this->published));

      mcms::flush();
      return true;
    }

    // Обычный пользователь.  Будем слать мыло.

    // Загрузим информацию о текущем пользователе.
    $me = Node::load(array('class' => 'user', 'login' => $user->getName()));

    // Выпускающий редактор не определён -- ничего не делаем.
    if (empty($me->publisher))
      return false;

    // Загрузим выпускающего.
    try {
      $boss = Node::load(array('class' => 'user', 'login' => $me->publisher));
    } catch (ObjectNotFoundException $e) { }

    // Почтовый адрес неизвестен -- ничего не делаем.
    if (empty($boss->email))
      return false;

    // Формируем сообщение и отправляем.
    $message = t("<p>Пользователь %user просит опубликовать документ типа &laquo;%class&raquo; с заголовком &laquo;%name&raquo;.&nbsp; Вы можете <a href='%editlink'>отредактировать</a> или сразу <a href='%publishlink'>опубликовать</a> его.</p>", array(
      '%user' => "<a href='http://{$_SERVER['HTTP_HOST']}/admin/node/{$me->id}/edit/'>{$me->name}</a>",
      '%class' => $this->class,
      '%name' => $this->name,
      '%editlink' => "http://{$_SERVER['HTTP_HOST']}/admin/node/{$this->id}/edit/?BebopNode.rev={$this->rid}",
      '%publishlink' => "http://{$_SERVER['HTTP_HOST']}/admin/node/{$this->id}/publish/?BebopNode.rev={$this->rid}",
      ));

    BebopMimeMail::send(null, $boss->email, "Обновление на сайте {$_SERVER['HTTP_HOST']}", $message);

    return false;
  }

  public function unpublish()
  {
    $user = mcms::user();

    if ($user->hasGroup('Publishers') or ($this->class == 'moduleinfo' and $user->hasGroup('Developers'))) {
      if (null === $this->id)
        $this->save();

      mcms::db()->exec("UPDATE `node` SET `published` = 0 WHERE `id` = :id", array(':id' => $this->id));
      mcms::flush();
      return true;
    }

    return false;
  }

  public function duplicate()
  {
    if (null !== ($id = $this->id)) {
      $this->id = null;
      $this->data['published'] = false;
      $this->data['deleted'] = false;
      $this->data['code'] = null;

      $this->save();

      $pdo = mcms::db();
      $params = array(':new' => $this->id, ':old' => $id);

      // Копируем права.
      $pdo->exec("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`)"
        ."SELECT :new, `uid`, `c`, `r`, `u`, `d` FROM `node__access` WHERE `nid` = :old", $params);

      // Копируем связи с другими объектами.
      $pdo->exec("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) "
        ."SELECT :new, `nid`, `key` FROM `node__rel` WHERE `tid` = :old", $params);
      $pdo->exec("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) "
        ."SELECT `tid`, :new, `key` FROM `node__rel` WHERE `nid` = :old", $params);

      mcms::flush();
    }
  }

  public function erase()
  {
    if (!$this->deleted)
      throw new ForbiddenException(t('Невозможно окончательно удалить объект, который не был помещён в корзину.'));

    if (!$this->checkPermission('d'))
      throw new ForbiddenException(t('У вас нет прав на удаление этого объекта.'));

    $pdo = mcms::db();

    $meta = $pdo->getResult("SELECT `left`, `right`, `right` - `left` + 1 AS `width` FROM `node` WHERE `id` = :id", array(':id' => $this->id));

    $pdo->exec("DELETE FROM `node` WHERE `left` BETWEEN :left AND :right", array(':left' => $meta['left'], ':right' => $meta['right']));
    $pdo->exec("UPDATE `node` SET `right` = `right` - :width WHERE `right` > :right ORDER BY `right` ASC", $args = array(':width' => $meta['width'], ':right' => $meta['right']));
    $pdo->exec("UPDATE `node` SET `left` = `left` - :width WHERE `left` > :right ORDER BY `left` ASC", $args);

    mcms_log('erase', $this->id);

    mcms::flush();
  }

  // Создаём новый объект.
  public static function create($class, array $data = null)
  {
    if (!class_exists($host = ucfirst(strtolower($class)) .'Node'))
      $host = 'Node';

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

  // Удаление ноды.
  public function delete()
  {
    if ($this->id === null)
      throw new InvalidArgumentException(t("Попытка удаления несохранённой ноды."));

    if (!$this->checkPermission('d'))
      throw new ForbiddenException(t("У вас нет прав на удаление объекта &laquo;<a href='@link'>%name</a>&raquo;.", array(
        '@link' => "/admin/node/{$this->id}/edit/?destination=". urlencode(empty($_GET['destination']) ? '/admin/content/' : $_GET['destination']),
        '%name' => $this->name,
        )));

    $pdo = mcms::db();
    $pdo->exec("UPDATE `node` SET `deleted` = 1 WHERE id = :nid", array('nid' => $this->id));

    mcms_log('delete', $this->id);

    mcms::flush();
  }

  public function undelete()
  {
    if (empty($this->deleted))
      throw new InvalidArgumentException(t("Попытка восстановления неудалённой ноды."));

    if ($this->id === null)
      throw new InvalidArgumentException(t("Попытка удаления несохранённой ноды."));

    if (!$this->checkPermission('d'))
      throw new ForbiddenException(t("У вас нет прав на удаление объекта &laquo;%name&raquo;.", array('%name' => $this->name)));

    $this->deleted = false;
    $this->save(false);

    mcms_log('undelete', $this->id);

    mcms::flush();
  }

  // Загружает дерево дочерних объектов, формируя вложенные массивы children.
  public function loadChildren()
  {
    // Загружаем детей в плоский список.
    $children = Node::find(array(
      'class' => $this->class,
      'left' => array('>'. $this->left, '<'. $this->right),
      '#sort' => array(
        'left' => 'asc',
        ),
      ));

    // Превращаем плоский список в дерево.
    $this->data['children'] = $this->make_tree($children);
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
  public function getParents($current = true)
  {
    $sql = "SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`code`, `parent`.`class`, `rev`.`name`, `rev`.`data` "
      ."FROM `node` AS `self`, `node` AS `parent`, `node__rev` AS `rev` "
      ."WHERE `self`.`left` BETWEEN `parent`.`left` AND `parent`.`right` AND `self`.`id` = {$this->id} AND `rev`.`rid` = `parent`.`rid` "
      ."ORDER BY `parent`.`left` -- NodeBase::getParents({$this->id})";

    $data = Tagger::getInstance()->getChildrenData($sql);

    if (!$current)
      foreach ($data as $k => $v)
        if ($v['id'] == $this->id)
          unset($data[$k]);

    return $data;
  }

  // Применяет к объекту шаблон.  Формат имени шаблона: префикс.имя.tpl, префикс можно
  // передать извне (по умолчанию используется "doc").  Если массив с данными пуст,
  // будут использоваться данные текущего объекта.
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

  public function linkListParents($class = null, $idsonly = false)
  {
    $params = array(':nid' => $this->id);
    $sql = "SELECT `r`.`tid`, `r`.`key` FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`tid` WHERE `n`.`deleted` = 0 AND `r`.`nid` = :nid";

    if (null !== $class) {
      $sql .= " AND `n`.`class` = :class";
      $params[':class'] = $class;
    }

    $sql .= sprintf(" ORDER BY `r`.`order` ASC -- linkListParents(%u, %s, %d)", $this->id, $class ? $class : 'NULL', $idsonly);

    $pdo = mcms::db();

    if ($idsonly)
      return $pdo->getResultsV("tid", $sql, $params);
    else
      return $pdo->getResults($sql, $params);
  }

  public function linkListChildren($class = null, $idsonly = false)
  {
    $params = array(':tid' => $this->id);
    $sql = "SELECT `r`.`nid`, `r`.`key` FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`nid` WHERE `n`.`deleted` = 0 AND `r`.`tid` = :tid";

    if (null !== $class) {
      $sql .= " AND `n`.`class` = :class";
      $params[':class'] = $class;
    }

    $sql .= sprintf(" ORDER BY `r`.`order` ASC -- linkListChildren(%u, %s, %d)", $this->id, $class ? $class : 'NULL', $idsonly);

    $pdo = mcms::db();

    if ($idsonly)
      return $pdo->getResultsV("nid", $sql, $params);
    else
      return $pdo->getResults($sql, $params);
  }

  public function linkAddParent($parent_id, $key = null)
  {
    if (null === $this->id)
      $this->save();

    $pdo = mcms::db();

    $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent_id, ':nid' => $this->id));

    if (null !== $key)
      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `key` = :key", array(':tid' => $parent_id, ':key' => $key));

    $pdo->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order)", array(
      ':tid' => $parent_id,
      ':nid' => $this->id,
      ':key' => $key,
      ':order' => self::getNextOrder($parent_id),
      ));

    mcms::flush();
  }

  public function linkAddChild($child_id, $key = null)
  {
    $pdo = mcms::db();

    $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $this->id, ':nid' => $child_id));

    if (null !== $key)
      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `key` = :key", array(':tid' => $this->id, ':key' => $key));

    $pdo->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order)", array(
      ':tid' => $this->id,
      ':nid' => $child_id,
      ':key' => $key,
      ':order' => self::getNextOrder($this->id),
      ));

    mcms::flush();
  }

  public function linkRemoveParent($parent_id)
  {
    $pdo = mcms::db();

    $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent_id, ':nid' => $this->id));

    mcms::flush();
  }

  public function linkRemoveChild($child_id = null, $key = null)
  {
    $pdo = mcms::db();

    if (null !== $child_id)
      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $this->id, ':nid' => $child_id));
    elseif (null !== $key)
      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `key` = :key", array(':tid' => $this->id, ':key' => $key));

    mcms::flush();
  }

  public function linkSetParents(array $list, $class = null, array $available = null)
  {
    $pdo = mcms::db();
    $xtra = '';

    if (null !== $available)
      $xtra = ' AND `tid` IN ('. join(', ', $available) .')';

    if (null === $class)
      $pdo->exec("DELETE FROM `node__rel` WHERE `nid` = :nid{$xtra} -- Node::linkSetParents({$this->id})",
        array(':nid' => $this->id));
    else
      $pdo->exec("DELETE FROM `node__rel` WHERE `nid` = :nid AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = :class){$xtra} -- Node::linkSetParents({$this->id}, {$class})",
        array(':nid' => $this->id, ':class' => $class));

    foreach ($list as $item) {
      if (is_array($item))
        $params = array(
          ':tid' => $item['id'],
          ':nid' => $this->id,
          ':key' => empty($item['key']) ? null : $item['key'],
          ':order' => self::getNextOrder($item['id']),
          );
      else
        $params = array(
          ':tid' => $item,
          ':nid' => $this->id,
          ':key' => null,
          ':order' => self::getNextOrder($item),
          );

      $pdo->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order) "
        ."-- Node::linkSetParents({$this->id})", $params);
    }

    mcms::flush();
  }

  public function linkSetChildren(array $list, $class = null)
  {
    $pdo = mcms::db();

    if (null === $class)
      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid -- Node::linkSetChildren({$this->id})",
        array(':tid' => $this->id));
    else
      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :tid AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = :class) -- Node::linkSetChildren({$this->id}, {$class})",
        array(':tid' => $this->id, ':class' => $class));

    $order = self::getNextOrder($this->id);

    foreach ($list as $item) {
      if (is_array($item))
        $params = array(
          ':tid' => $this->id,
          ':nid' => $item['id'],
          ':key' => empty($item['key']) ? null : $item['key'],
          ':order' => $order++,
          );
      else
        $params = array(
          ':tid' => $this->id,
          ':nid' => $item,
          ':key' => null,
          ':order' => $order++,
          );

      $pdo->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order) "
        ."-- Node::linkSetChildren({$this->id})", $params);
    }

    mcms::flush();
  }

  private static function getNextOrder($tid)
  {
    return mcms::db()->getResult("SELECT MAX(`order`) FROM `node__rel` WHERE `tid` = :tid", array(':tid' => $tid)) + 1;
  }


  public function moveBefore($tid)
  {
    // Прописываем дефолтные порядки.
    $this->orderFix();

    $pdo = mcms::db();
    $he = $pdo->getResult("SELECT `nid`, `tid`, `order` FROM `node__rel` WHERE `nid` >= :nid", array(':nid' => $tid));
    $me = $pdo->getResults("SELECT `nid`, `tid`, `order` FROM `node__rel` WHERE `tid` = :tid AND `order` >= :order ORDER BY `order` DESC", array(':tid' => $he['tid'], ':order' => $he['order']));

    $orderTo = $he['order'];
    foreach ($me as $k => $node) {
      $pdo->exec('UPDATE `node__rel` SET `order` = :order WHERE `nid` = :nid', array(':nid' => $node['nid'], ':order' => $node['order'] + 1));
    }
    $pdo->exec('UPDATE `node__rel` SET `order` = :order WHERE `nid` = :nid', array(':nid' => $me[0]['nid'], ':order' => $orderTo));

    mcms::flush();
  }

  public function moveAfter($tid)
  {
    // Прописываем дефолтные порядки.
    $this->orderFix();

    $pdo = mcms::db();
    $he = $pdo->getResult("SELECT `nid`, `tid`, `order` FROM `node__rel` WHERE `nid` >= :nid", array(':nid' => $tid));
    $me = $pdo->getResults("SELECT `nid`, `tid`, `order` FROM `node__rel` WHERE `tid` = :tid AND `order` <= :order ORDER BY `order` ASC", array(':tid' => $he['tid'], ':order' => $he['order']));

    $orderTo = $he['order'];
    foreach ($me as $k => $node) {
      $pdo->exec('UPDATE `node__rel` SET `order` = :order WHERE `nid` = :nid', array(':nid' => $node['nid'], ':order' => $node['order'] - 1));
    }
    $pdo->exec('UPDATE `node__rel` SET `order` = :order WHERE `nid` = :nid', array(':nid' => $me[0]['nid'], ':order' => $orderTo));

    mcms::flush();
  }

  // РАБОТА С ПРАВАМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Permissions

  public function getAccess()
  {
    static $default = null;

    $pdo = mcms::db();

    // Формируем список групп.
    if ($default === null)
      $default = $pdo->getResultsK("login", "SELECT `g`.`login`, CASE WHEN `r`.`name` IS NULL THEN `g`.`login` ELSE `r`.`name` END AS `name`, 0 AS `c`, 0 AS `r`, 0 AS `u`, 0 AS `d` FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` INNER JOIN `node_group` `g` ON `g`.`rid` = `n`.`rid` WHERE `n`.`class` = 'group' AND `n`.`deleted` = 0 ORDER BY `r`.`name`");

    $data = $default;

    // Формируем таблицу с правами.
    foreach ($pdo->getResultsK("login", "SELECT `g`.`login`, `a`.* FROM `node__access` `a` INNER JOIN `node` `n` ON `n`.`id` = `a`.`uid` INNER JOIN `node_group` `g` ON `g`.`rid` = `n`.`rid` WHERE `a`.`nid` = :nid", array(':nid' => $this->id)) as $group => $perms) {
      $data[$group]['c'] = $perms['c'];
      $data[$group]['r'] = $perms['r'];
      $data[$group]['u'] = $perms['u'];
      $data[$group]['d'] = $perms['d'];
    }

    return $data;
  }

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
        );

      if (is_numeric($uid)) {
        $args[':uid'] = $uid;
        $pdo->exec($sql = "REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`) VALUES (:nid, :uid, :c, :r, :u, :d)", $args);
      } else {
        $args[':name'] = $uid;
        $pdo->exec($sql = "REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`) SELECT :nid, `n`.`id`, :c, :r, :u, :d FROM `node` `n` INNER JOIN `node_group` `g` ON `g`.`rid` = `n`.`rid` WHERE `g`.`login` = :name", $args);
      }
    }

    mcms::flush();
  }

  public function checkPermission($perm)
  {
    if ($perm != 'c' and $perm != 'r' and $perm != 'u' and $perm != 'd')
      throw new InvalidArgumentException("Wrong argument for checkPermission(): '{$perm}', expected 'c', 'r', 'u' or 'd'.");

    if (empty($_SERVER['HTTP_HOST']))
      return true;

    $user = mcms::user();
    if ($user->getName() == 'root')
      return true;

    // При проверки прав на создание новых документов обращаемся к типу документа.
    if (null === ($nid = $this->id) and 'c' == $perm) {
      $schema = TypeNode::getSchema($this->class);
      $nid = $schema['id'];
    }

    $grant = mcms::db()->getResult("SELECT 1 FROM `node` WHERE `id` = :nid AND `id` IN (PERMCHECK:{$perm}) -- NodeBase::checkPermission({$perm})", array(':nid' => $nid));

    return $grant ? true : false;
  }

  // Если есть ноды с пустым `order`, надо бы их починить

  private function orderFix()
  {
    $pdo = mcms::db();
    $pdo->exec("UPDATE `node__rel` SET `order` = `nid` WHERE `order` IS NULL AND `tid` = :tid AND `key` IS NULL", array(':tid' => $parent));
  }

  // ИЗМЕНЕНИЕ ПОРЯДКА ДОКУМЕНТОВ.

  public function orderUp($parent = null)
  {
    if (null === $parent) {
      Tagger::getInstance()->nodeMoveUp($this->id);
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

  public function orderDown($parent = null)
  {
    if (null === $parent) {
      Tagger::getInstance()->nodeMoveDown($this->id);
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

  // ОПРЕДЕЛЕНИЕ СОСЕДЕЙ.

  public function getNeighbors($parent, array $classes = null)
  {
    $pdo = mcms::db();

    if (null === ($order = $pdo->getResult("SELECT `order` FROM `node__rel` WHERE `tid` = :tid AND `nid` = :nid", array(':tid' => $parent, ':nid' => $this->id))))
      return null;

    if (null === $classes)
      $filter = '';
    else
      $filter = " AND `n`.`class` IN ('". join("', '", $classes) ."')";

    $left = $pdo->getResult($sql1 = "SELECT `n`.`id` FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`nid` "
      ."WHERE `tid` = :tid{$filter} AND `n`.`deleted` = 0 AND `n`.`published` = 1 AND `n`.`id` IN (PERMCHECK:r) "
      ."AND `n`.`class` = :class "
      ."AND `r`.`order` < :order ORDER BY `r`.`order` DESC LIMIT 1",
      array(':tid' => $parent, ':order' => $order, ':class' => $this->class));

    $right = $pdo->getResult($sql2 = "SELECT `n`.`id` FROM `node__rel` `r` INNER JOIN `node` `n` ON `n`.`id` = `r`.`nid` "
      ."WHERE `tid` = :tid{$filter} AND `n`.`deleted` = 0 AND `n`.`published` = 1 AND `n`.`id` IN (PERMCHECK:r) "
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

  public function formGet($simple = false)
  {
    if (null !== $this->id and !$this->checkPermission('u'))
      throw new ForbiddenException(t('У вас недостаточно прав для редактирования этого документа.'));
    elseif (null === $this->id and !$this->checkPermission('c'))
      throw new ForbiddenException(t('У вас недостаточно прав для создания такого документа.'));

    $tabs = array();
    $user = mcms::user();

    // Формируем вкладку с содержимым документа.
    $tabs['content'] = new FieldSetControl(array(
      'name' => 'content',
      'label' => t('Основные свойства'),
      ));

    $schema = TypeNode::getSchema($this->class);

    if (!$simple and (null !== ($intro = $this->formGetIntro())))
      $tabs['content']->addControl($intro);

    if (array_key_exists('fields', $schema) and is_array($schema['fields'])) {
      foreach ($schema['fields'] as $k => $v) {
        if ($k == 'fk' and $simple)
          continue;

        if (mcms_ctlname($v['type']) != 'ArrayControl') {
          if (mcms_ctlname($v['type']) == 'AttachmentControl') {
            $v['value'] = 'node_content_files['. $k .']';
            $v['medium'] = true;
          } else {
            $v['value'] = 'node_content_'. $k;
          }

          if ($k == 'title')
            $v['class'] = 'form-title';
          elseif ($k == 'name' and !array_key_exists('title', $schema['fields']))
            $v['class'] = 'form-title';

          $v['wrapper_id'] = "control-node-{$k}-wrapper";

          $tabs['content']->addControl(Control::make($v));
        }
      }
    }

    if (!empty($schema['hasfiles']))
      $tabs['content']->addControl(new SetControl(array(
        'value' => 'node_ftp_files',
        'label' => t('Прикрепить файлы с FTP'),
        'options' => FileNode::listFilesOnFTP(),
        )));

    if (!$simple and empty($schema['notags']) and null !== ($tab = $this->formGetSections($schema)))
      $tabs['sections'] = $tab;

    if (!$simple and (null !== ($tab = $this->formGetFilesTab())))
      $tabs['files'] = $tab;

    if ($simple) {
      if (null === $this->id)
        $title = trim($schema['title']);
      else
        $title = $this->name;
    } else {
      if (null === $this->id)
        $title = t('Создание объекта типа "%type"', array('%type' => trim($schema['title'])));
      else
        $title = t('%name (редактирование)', array('%name' => empty($this->name) ? t('Безымянный документ') : trim($this->name)));
    }

    // Формируем окончательную форму.
    $form = new Form(array(
      'title' => $title,
      ));
    $form->addControl(new HiddenControl(array('value' => 'node_content_id')));

    if (null === $this->id) {
      $form->addControl(new HiddenControl(array('value' => 'node_content_class')));
      $form->addControl(new HiddenControl(array('value' => 'node_content_parent_id')));
    }

    foreach ($tabs as $tab)
      $form->addControl($tab);

    $form->addControl(new SubmitControl(array(
      'text' => 'Сохранить',
      )));

    return $form;
  }

  private function formGetIntro()
  {
    $schema = TypeNode::getSchema($this->class);

    $description = empty($schema['description']) ? '' : '&nbsp; '. t('Его описание: %description.', array('%description' => rtrim($schema['description'], '.')));

    $intro = array();

    /*
    if (null === $this->id)
      $intro[] = t('Вы создаёте документ типа &laquo;%type&raquo;.', array('%type' => $schema['title'])) . $description;
    else
      $intro[] = t('Вы изменяете документ типа &laquo;%type&raquo;.', array('%type' => $schema['title'])) . $description;
    */

    if (mcms::user()->hasGroup('Schema Managers') and $this->class != 'type' and substr($_SERVER['REQUEST_URI'], 0, 7) == '/admin/')
      $intro[] = t("Вы можете <a href='@typelink'>настроить этот тип</a>, добавив новые поля.", array(
        '@typelink' => "/admin/node/{$schema['id']}/edit/?destination=". urlencode($_SERVER['REQUEST_URI']),
        ));

    if (!empty($schema['fields']))
      foreach ($schema['fields'] as $k => $v)
        if (!empty($v['required'])) {
          $intro[] = t('Поля, отмеченные звёздочкой, обязательны для заполнения.');
          break;
        }

    if (!empty($intro))
      return new InfoControl(array(
        'text' => '<p>'. join('</p><p>', $intro) .'</p>',
        ));

    return null;
  }

  private function formGetFilesTab()
  {
    $schema = TypeNode::getSchema($this->class);

    if (empty($schema['hasfiles']))
      return null;

    $tab = new FieldSetControl(array(
      'name' => 'files',
      'label' => t('Файлы'),
      ));

    $tab->addControl(new FileListControl(array(
      'value' => 'node_content_files',
      )));

    $tab->addControl(new AttachmentControl(array(
      'extended' => true,
      'value' => 'node_content_files[__bebop]',
      'uploadtxt' => t('Загрузить'),
      'unzip' => true,
      )));

    return $tab;
  }

  private function getSectionsForThisType()
  {
    $list = Node::load(array('class' => 'type', 'name' => $this->class))->linkListParents('tag', true);
    return $list;
  }

  private function formGetSections(array $schema)
  {
    $options = array();

    switch (count($enabled = $this->getSectionsForThisType())) {
    case 0:
      break;
    case 1:
      return null;
    default:
      $options['enabled'] = $enabled;
    }

    $tab = new FieldSetControl(array(
      'name' => 'sections',
      'label' => t('Разделы'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'node_tags',
      'label' => t('Поместить документ в разделы'),
      'options' => TagNode::getTags('select', $options),
      )));

    return $tab;
  }

  public function formGetData()
  {
    $schema = TypeNode::getSchema($this->class);

    $data = array(
      'node_content_id' => $this->id,
      'node_content_class' => $this->class,
      'node_content_parent_id' => $this->parent_id,
      );

    if (array_key_exists('fields', $schema) and is_array($schema['fields']))
      foreach ($schema['fields'] as $k => $v) {
        if (mcms_ctlname($v['type']) == 'AttachmentControl')
          $value = empty($this->files[$k]) ? null : $this->files[$k];
        else
          $value = $this->$k;

        $data['node_content_'. $k] = $value;
      }

    if (!empty($this->files)) {
      foreach ($this->files as $key => $file) {
        $data['node_content_files['. $key .']'] = $file;
      }
    }

    if (empty($schema['notags'])) {
      $data['node_tags'] = $this->linkListParents('tag', true);
    }

    return $data;
  }

  public function formProcess(array $data)
  {
    $schema = TypeNode::getSchema($this->class);

    if (array_key_exists('fields', $schema)) {
      foreach ($schema['fields'] as $k => $v) {
        if ($k != 'parent_id' and $k != 'fields' and $k != 'config') {
          if (mcms_ctlname($v['type']) != 'AttachmentControl') {
            $key = 'node_content_'. $k;
            $this->$k = array_key_exists($key, $data) ? $data[$key] : null;
          }
        }
      }
    }

    if (array_key_exists('#node_override', $data))
        $this->data = array_merge($this->data, $data['#node_override']);

    $this->save();

    if (!empty($data['node_content_files'])) {
      foreach ($data['node_content_files'] as $field => $fileinfo) {
        if (!is_array($fileinfo))
          continue;

        $fkey = (!is_numeric($field) and $field != '__bebop') ? $field : null;

        // Удаление (отвязка) прикреплённого файла.
        if (!empty($fileinfo['delete'])) {
          $this->linkRemoveChild(empty($fileinfo['id']) ? null : $fileinfo['id'], $field);
        }

        // Загрузка нового файла.
        elseif (!empty($fileinfo['tmp_name'])) {
          $fileinfo['parent_id'] = $this->id;

          $node = Node::create('file');
          $node->import($fileinfo);

          $this->linkAddChild($node->id, $fkey);
        }

        // Подключение файла.
        elseif (!empty($fileinfo['id'])) {
          $this->linkAddChild($fileinfo['id'], $fkey);
        }

        // Обновление других свойств — поддерживается только для файлов в дополнительной вкладке,
        // у которых числовые индексы; для полей типа «файл» эта возможность отсутствует.
        elseif (is_numeric($field)) {
          $file = Node::load(array('class' => 'file', 'id' => $field));

          if (!empty($fileinfo['unlink']))
            $file->linkRemoveParent($this->id);

          else {
            if (!empty($fileinfo['name']))
              $file->data['name'] = $fileinfo['name'];
            if (!empty($fileinfo['description']))
              $file->data['description'] = $fileinfo['description'];

            if (!empty($fileinfo['tmp_name']))
              $file->import($fileinfo);

            $file->save();
          }
        }
      }
    }

    if (empty($schema['notags'])) {
      $sections = $this->getSectionsForThisType();

      if (count($sections) == 1)
        $data['node_tags'] = $sections;
      elseif (empty($data['node_tags']) or !is_array($data['node_tags']))
        $data['node_tags'] = array();

      $this->linkSetParents($data['node_tags'], 'tag');
    }

    if (!empty($schema['hasfiles']) and !empty($data['node_ftp_files'])) {
      FileNode::getFilesFromFTP($data['node_ftp_files'], $this->id);
    }
  }
};
