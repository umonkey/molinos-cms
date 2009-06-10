<?php
/**
 * Базовый класс для всех объектов, хранимых в БД.
 *
 * Реализует функции, общие для всех объектов, но не имеющие отношения к
 * взаимодействию с БД — этот код вынесен в класс NodeBase.
 *
 * @package Molinos CMS
 * @subpackage mod_base
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Базовый класс для всех объектов, хранимых в БД.
 *
 * Реализует функции, общие для всех объектов, но не имеющие отношения к
 * взаимодействию с БД — этот код вынесен в класс NodeBase.
 */
class Node
{
  /**
   * Содержимое полей объекта.
   */
  private $data;

  /**
   * SQL инструкции, выполняемые при сохранении объекта.
   */
  private $onsave = array();

  /**
   * Ссылка на базу данных.  Заполняется конструктором.
   */
  private $isnew = true;

  /**
   * Содержит true, если свойства объекта изменялись.
   */
  private $dirty = false;

  /**
   * Инициализация объекта. Извне следует использовать ::create().
   */
  protected function __construct(PDO_Singleton $db, array $data = array())
  {
    // Разворачиваем сериализованные данные.  Свойства, пересекающиеся
    // с базовыми, уничтожаются.
    if (array_key_exists('data', $data)) {
      if (is_array($tmp = @unserialize($data['data'])))
        $data = array_merge($tmp, $data);
      unset($data['data']);
    }

    $this->db = $db;
    $this->data = $data;
    $this->isnew = empty($data['id']);

    if (!empty($this->data['id'])) {
      $this->retrieve();
      mcms::flog("node[{$this->data['id']}]: read from DB (slow).");
    } else {
      $this->dirty = true;
    }
  }

  /**
   * Загрузка привязанных объектов.
   */
  private function retrieve()
  {
    if (!empty($this->data['id'])) {
      $extra = array();

      $rows = $this->getDB()->getResults("SELECT `node__rel`.`key`, `node`.* FROM `node__rel` "
        . "INNER JOIN `node` ON `node`.`id` = `node__rel`.`nid` "
        . "WHERE `node`.`deleted` = 0 AND `node__rel`.`tid` = ? "
        . "AND `node__rel`.`tid` <> `node__rel`.`nid` " // предотвращение простейшей рекурсии
        . "AND `node__rel`.`key` IS NOT NULL", array($this->data['id']));

      foreach ($rows as $data) {
        // Звездой отмечены массивы.
        if ('*' === substr($data['key'], -1))
          continue;

        $field = $data['key'];
        unset($data['key']);

        if (isset($extra[$field])) {
          if (!is_array($extra[$field]))
            $extra[$field] = array($extra[$field]);
          $extra[$field][] = Node::create($data, $this->getDB());
        } else {
          $extra[$field] = Node::create($data, $this->getDB());
        }
      }

      $this->data = array_merge($this->data, $extra);
    }
  }

  /**
   * Создание нового объекта.  Если БД не указана, используется текущее подключение.
   */
  public static function create($fields, PDO_Singleton $db = null)
  {
    if (!is_array($fields))
      $fields = array(
        'class' => $fields,
        );

    if (empty($fields['class']))
      throw new InvalidArgumentException(t('Не указан тип создаваемой ноды.'));

    if (!class_exists($factory = $fields['class'] . 'Node'))
      $factory = __CLASS__;

    if (null === $db)
      $db = Context::last()->db;

    return new $factory($db, $fields);
  }

  /**
   * Загрузка конкретной ноды по id.
   *
   * Параметры:
   *   $query — идентификатор ноды или описание запроса.
   *   $db — база данных. Если не указано, используется текущее подключение.
   *
   * Если объект не найден, кидает ObjectNotFoundException; если найдено больше
   * одного объекта, кидает RuntimeException.
   */
  public static function load($query, PDO $db = null)
  {
    if (null === $db)
      $db = Context::last()->db;

    if (!is_array($query))
      $query = array('id' => $query);

    if (count($nodes = (array)Node::find($query, $db)) > 1)
      throw new RuntimeException(t('Запрос к Node::load() вернул более одного объекта.'));
    elseif (empty($nodes))
      throw new ObjectNotFoundException();

    return array_shift($nodes);
  }

  /**
   * Загрузка нод из БД.
   *
   * Параметры:
   *   $query — запрос.
   *   $db — БД. Если не указана, используется текущее подключение.
   */
  public static function find($query, PDO_Singleton $db = null)
  {
    if (is_array($query))
      $query = Query::build($query);
    elseif (!($query instanceof Query))
      throw new InvalidArgumentException(t('Запрос должен быть массивом или объектом Query.'));

    if (null === $db)
      $db = Context::last()->db;

    list($sql, $params) = $query->getSelect();

    $sth = $db->prepare($sql);
    $sth->execute($params);

    $result = array();
    while ($row = $sth->fetch(PDO::FETCH_ASSOC))
      $result[$row['id']] = Node::create($row, $db);

    return $result;
  }

  /**
   * Поиск нод, результат — в XML.
   */
  public static function findXML($query, PDO_Singleton $db = null)
  {
    if (is_array($query))
      $query = Query::build($query);
    elseif (!($query instanceof Query))
      throw new InvalidArgumentException(t('Запрос должен быть массивом или объектом Query.'));

    if (null === $db)
      $db = Context::last()->db;

    list($sql, $params) = $query->getSelect(array('xml'));

    $sel = $db->prepare($sql);
    $sel->execute($params);

    $output = '';
    while ($xml = $sel->fetchColumn(0))
      $output .= $xml;

    return $output;
  }

  /**
   * Возвращает количество нод, удовлетворяющих запросу.
   */
  public static function count($query, PDO_Singleton $db = null)
  {
    if (is_array($query))
      $query = Query::build($query);
    elseif (!($query instanceof Query))
      throw new InvalidArgumentException(t('Запрос должен быть массивом или объектом Query.'));

    if (null === $db)
      $db = Context::last()->db;

    list($sql, $params) = $query->getCount();

    $sth = $db->prepare($sql);
    $sth->execute($params);

    return intval($sth->fetchColumn(0));
  }

  /**
   * Возвращает родителей текущей ноды.
   */
  public function getParents()
  {
    return self::find(array(
      'deleted' => 0,
      'id' => self::getNodeParentIds($this->getDB(), $this->data['id']),
      '#sort' => 'left',
      ), $this->getDB());
  }

  /**
   * Возвращает идентификаторы родителей указанной ноды.
   */
  public static function getNodeParentIds(PDO_Singleton $db, $id)
  {
    $sql = "SELECT `parent`.`id` as `id` "
      ."FROM `node` AS `self`, `node` AS `parent` "
      ."WHERE `self`.`left` BETWEEN `parent`.`left` "
      ."AND `parent`.`right` AND `self`.`id` = ? "
      ."ORDER BY `parent`.`left`";
    return $db->getResultsV("id", $sql, array($id));
  }

  /**
   * Возвращает родителей текущей ноды в XML.
   */
  public function getParentsXML()
  {
    $sql = "SELECT `parent`.`id` as `id` "
      ."FROM `node` AS `self`, `node` AS `parent` "
      ."WHERE `self`.`left` BETWEEN `parent`.`left` "
      ."AND `parent`.`right` AND `self`.`id` = ? "
      ."ORDER BY `parent`.`left`";

    return self::findXML(array(
      'deleted' => 0,
      'id' => (array)$this->getDB()->getResultsV("id", $sql, array($this->data['id'])),
      '#sort' => 'left',
      ), $this->getDB());
  }

  /**
   * Получение списка связанных объектов.
   * TODO: оптимизировать.
   */
  public function getLinked($class = null, $ids = false)
  {
    $result = array();

    if (!empty($this->data['id'])) {
      $params = array($this->data['id']);
      $sql = "SELECT `nid` FROM `node__rel` WHERE `tid` = ?";

      if (null !== $class) {
        $sql .= " AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0)";
        $params[] = $class;
      } else {
        $sql .= " AND `nid` NOT IN (SELECT `id` FROM `node` WHERE `deleted` = 1)";
      }

      foreach ((array)$this->getDB()->getResultsV("nid", $sql, $params) as $id)
        $result[] = $ids
          ? $id
          : self::load($id, $this->getDB());
    }

    return $result;
  }

  /**
   * Получение списка объектов, к которым привязан текущий.
   * TODO: оптимизировать.
   */
  public function getLinkedTo($class = null, $ids = false)
  {
    $result = array();

    if (!empty($this->data['id'])) {
      $params = array($this->data['id']);
      $sql = "SELECT `tid` FROM `node__rel` WHERE `nid` = ?";

      if (null !== $class) {
        $sql .= " AND `tid` IN (SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0)";
        $params[] = $class;
      } else {
        $sql .= ' AND `tid` NOT IN (SELECT `id` FROM `node` WHERE `deleted` = 1)';
      }

      foreach ((array)$this->getDB()->getResultsV("tid", $sql, $params) as $id)
        $result[] = $ids
          ? $id
          : self::load($id, $this->getDB());
    }

    return $result;
  }

  /**
   * Возвращает ссылку на БД.
   */
  public function getDB()
  {
    return $this->db
      ? $this->db
      : Context::last()->db;
  }

  /**
   * Доступ к свойствам объекта.
   */
  private final function __get($key)
  {
    return array_key_exists($key, $this->data)
      ? $this->data[$key]
      : null;
  }

  private final function __set($key, $value)
  {
    $this->data[$key] = $value;
    $this->dirty = true;
  }

  private final function __isset($key)
  {
    return isset($this->data[$key]);
  }

  private final function __unset($key)
  {
    if (array_key_exists($key, $this->data)) {
      unset($this->data[$key]);
      $this->dirty = true;
    }
  }

  /**
   * Заглушка для неверных вызовов.
   */
  private final function __call($method, $args)
  {
    mcms::debug('Bad method call', $method);
    throw new RuntimeException(t('Метод %class::%method() не существует.', array(
      '%class' => get_class($this),
      '%method' => $method,
      )));
  }

  /**
   * Сериализация ноды: возвращаем только данные.
   */
  public function __sleep()
  {
    return array('data');
  }

  /**
   * Возвращает true, если объект создан с нуля, а не загружен из БД.
   */
  public function isNew()
  {
    return $this->isnew;
  }

  /**
   * Возвращает имя объекта.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Заставляет объект думать, что он изменился.
   */
  public function touch()
  {
    $this->dirty = true;
    return $this;
  }

  /**
   * Возвращает true, если у пользователя есть нужный доступ к объекту.
   */
  public function checkPermission($perm)
  {
    if (empty($_SERVER['HTTP_HOST']))
      return true;

    $user = Context::last()->user;

    if ('user' == $this->class and $user->id == $this->id and 'u' == $perm)
      return true;

    if (is_object($this->uid))
      $uid = $this->uid->id;
    elseif (is_numeric($this->uid))
      $uid = $this->uid;
    else
      $uid = null;

    if (null !== $uid and $uid == $user->id)
      if (in_array($perm, Structure::getInstance()->getOwnDocAccess($this->class)))
        return true;

    return $user->hasAccess($perm, $this->class);
  }

  /**
   * Кидает ForbiddenException, если у пользователя нет нужного доступа.
   */
  public function knock($mode)
  {
    if ($this->checkPermission($mode))
      return $this;
    else
      throw new ForbiddenException();
  }

  /**
   * Получение формы.
   */
  public function formGet($fieldName = null)
  {
    if (!$this->checkPermission($this->id ? 'u' : 'c'))
      throw new ForbiddenException(t('У вас недостаточно прав для работы с этим документом.'));

    $form = $this->getFormFields()->sort()->getForm(array(
      'action' => $this->getFormAction(),
      'title' => $this->getFormTitle(),
      ), $fieldName);

    if ($fieldName)
      $form->action .= '&field=' . urlencode($fieldName);

    $form->addControl(new SubmitControl(array(
      'text' => $this->getFormSubmitText(),
      )));

    if ($this->parent_id and !isset($schema['parent_id']))
      $form->addControl(new HiddenControl(array(
        'value' => 'parent_id',
        'default' => $this->parent_id,
        )));

    return $form;
  }

  /**
   * Возвращает контролы для формы.
   */
  public function getFormFields()
  {
    $schema = $this->getSchema();

    if (!$this->isNew() and isset($schema['parent_id']))
      unset($schema['parent_id']);

    if (!Context::last()->user->id and !$this->id and class_exists('CaptchaControl'))
      $schema['captcha'] = new CaptchaControl(array(
        'value' => 'captcha',
        'label' => t('Введите символы с картинки'),
        'required' => true,
        ));

    return $schema;
  }

  /**
   * Возвращает адрес отправки формы. Можно использовать для перегрузки.
   */
  public function getFormAction()
  {
    $next = empty($_GET['destination'])
      ? $this->getListURL()
      : $_GET['destination'];

    return $this->id
      ? "?q=nodeapi.rpc&action=edit&node={$this->id}&destination=". urlencode($next)
      : "?q=nodeapi.rpc&action=create&type={$this->class}&destination=". urlencode($next);
  }

  /**
   * Возвращает текст для кнопки сохранения формы.
   * Используется для перегрузки.
   */
  public function getFormSubmitText()
  {
    return t('Сохранить');
  }

  /**
   * Возвращает заголовок формы редактирования объекта.
   * Используется для перегрузки.
   */
  public function getFormTitle()
  {
    return $this->id
      ? $this->name
      : t('Добавление нового документа');
  }

  /**
   * Обрабатывает полученные от пользователя данные.
   */
  public function formProcess(array $data, $fieldName = null)
  {
    if (!$this->checkPermission($this->id ? 'u' : 'c'))
      throw new ForbiddenException(t('Ваших полномочий недостаточно '
        .'для редактирования этого объекта.'));

    $schema = $this->getFormFields();

    foreach ($schema as $name => $field) {
      if ($field->label and (null === $fieldName or $fieldName == $name)) {
        $value = array_key_exists($name, $data)
          ? $data[$name]
          : null;

        $field->set($value, $this, $data);
      }
    }

    return $this;
  }

  /**
   * Возвращает базовый набор полей.
   * Используется для перегрузки.
   */
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

  /**
   * Возвращает полное описание полей этого типа, хранимое в кэше.
   * Для построения форм следует использовать getFormFields().
   */
  public final function getSchema()
  {
    return Schema::load($this->getDB(), $this->class);
  }

  /**
   * Рендеринг объекта в HTML.
   *
   * Применяет к документу наиболее подходящий шаблон (class.* из шкуры «all»).
   *
   * @todo устранить.
   *
   * @param string $prefix не используется.
   *
   * @param string $theme не используется.
   *
   * @param array $data не используется.
   *
   * @return string полученный HTML-код, или NULL.
   */
  // Форматирует документ в соответствии с шаблоном.
  public function render($prefix = null, $theme = null, array $data = null)
  {
    // FIXME
    throw new RuntimeException(t('Функция не реализована.'));
  }

  public function getActionLinks()
  {
    $links = array();
    $ctx = Context::last();

    if ($this->checkPermission('u')) {
      $links['edit'] = array(
        'href' => 'admin/edit/'. $this->id
          . '?destination=CURRENT',
        'title' => t('Редактировать'),
        'icon' => 'edit',
        );
    }

    /*
    if ($this->checkPermission('c'))
      $links['clone'] = array(
        'href' => 'nodeapi/clone?node='. $this->id
          .'&destination=CURRENT',
        'title' => t('Клонировать'),
        'icon' => 'clone',
        );
    */

    if ($this->checkPermission('d')) {
      if ($this->deleted)
        $links['delete'] = array(
          'href' => 'nodeapi/undelete?node=' . $this->id
            .'&destination=CURRENT',
          'title' => t('Восстановить'),
          'icon' => 'delete',
          );
      else
        $links['delete'] = array(
          'href' => 'nodeapi/delete?node=' . $this->id
            .'&destination=admin/content/list/' . $this->class,
          'title' => t('Удалить'),
          'icon' => 'delete',
          );
    }

    if ($this->checkPermission('p') and !in_array($this->class, array('type')) and !$this->deleted) {
      if ($this->published) {
        $action = 'unpublish';
        $title = 'Скрыть';
      } else {
        $action = 'publish';
        $title = 'Опубликовать';
      }

      $links['publish'] = array(
        'href' => 'nodeapi/'. $action . '?node=' . $this->id
          .'&destination=CURRENT',
        'title' => t($title),
        'icon' => $action,
        );
    }

    if ($this->published and !$this->deleted and !in_array($this->class, array('domain', 'widget', 'type')))
      $links['locate'] = array(
        'href' => 'nodeapi/locate?node='. $this->id,
        'title' => t('Найти на сайте'),
        'icon' => 'locate',
        );

    if ('type' != $this->class) {
      try {
        $tmp = Node::load(array(
          'class' => 'type',
          'deleted' => 0,
          'name' => $this->class,
          ));
        $links['schema'] = array(
          'href' => 'admin/node/' . $tmp->id . '?destination=CURRENT',
          'title' => t('Настроить тип'),
          );
      } catch (Exception $e) { }
    }

    if ($ctx->canDebug()) {
      $links['dump'] = array(
        'href' => 'nodeapi/dump?node=' . $this->id,
        'title' => 'XML дамп',
        'icon' => 'dump',
        );
    }

    foreach ($ctx->registry->poll('ru.molinos.cms.node.actions', array($ctx, $this)) as $tmp)
      foreach ((array)$tmp['result'] as $k => $v)
        $links[$k] = $v;

    return $links;
  }

  public function getActionLinksXML()
  {
    $output = '';
    $links = $this->getActionLinks();

    foreach ($links as $k => $v) {
      if (is_array($v))
        $output .= html::em('link', array('name' => $k) + $v);
    }

    return empty($output)
      ? ''
      : html::em('links', $output);
  }

  public static function getSortedList($class, $field = 'name', $key = 'id')
  {
    $result = array();

    // Вывод дерева страниц и разделов
    if (in_array($class, array('tag', 'domain'))) {
      $result = array();
      foreach (Node::listChildren($class) as $item)
        $result[$item[0]] = str_repeat(' ', 2 * $item[2]) . $item[1];
    }

    // Вывод обычных списков
    else {
      foreach ((array)Node::find(array('class' => $class, 'deleted' => 0)) as $n) {
        $value = ('name' == $field)
          ? $n->getName()
          : $n->$field;

        if (empty($value))
          $value = $n->getName();

        $result[$n->$key] = $value;

        /*
        if ('name' != $field)
          $result[$n->$key] .= ' (' . $n->name . ')';
        */
      }

      asort($result);
    }

    return $result;
  }

  /**
   * Получение инеднтификаторов разделов, в которые можно поместить документ.
   */
  public function getEnabledSections()
  {
    $ctx = Context::last();

    $allowed = $ctx->db->getResultsV("id", "SELECT id FROM node WHERE class = 'tag' AND deleted = 0 AND id IN "
      . "(SELECT tid FROM node__rel WHERE nid IN "
      . "(SELECT `id` FROM `node` "
      . "WHERE `deleted` = 0 AND `class` = 'type' AND `name` = ?))",
      array($this->class));

    if (null === ($permitted = $ctx->user->getPermittedSections()))
      return array();

    return (null === $allowed)
      ? array_unique($permitted)
      : array_intersect($allowed, $permitted);
  }

  public function getImage()
  {
    switch ($this->filetype) {
    case 'image/jpeg':
    case 'image/pjpeg':
        $func = 'imagecreatefromjpeg';
        break;
    case 'image/png':
    case 'image/x-png':
        $func = 'imagecreatefrompng';
        break;
    case 'image/gif':
        $func = 'imagecreatefromgif';
        break;
    default:
        throw new RuntimeException(t('Файл %name не является картинкой.', array(
          '%name' => $this->filename,
          )));
    }

    if (!function_exists($func))
      throw new RuntimeException(t('Текущая конфигурация PHP не поддерживает работу с файлами типа %type.', array(
        '%type' => $this->filetype,
        )));

    $img = call_user_func($func, os::path(Context::last()->config->getPath('files'), $this->filepath));

    if (null === $img)
      throw new RuntimeException(t('Не удалось открыть файл %name (возможно, он повреждён).', array(
        '%name' => $this->filename,
        )));

    return $img;
  }

  public static function getNodesXML($em, array $nodes)
  {
    $output = '';

    foreach ($nodes as $node)
      $output .= $node->getXML($em);

    return empty($output)
      ? null
      : html::em($em . 's', $output);
  }

  /**
   * Возвращает список дочерних объектов в виде дерева.
   */
  public static function listChildren($class, $parent_id = null)
  {
    $db = Context::last()->db;

    if (null === $parent_id) {
      $sql = "SELECT `id`, `parent_id`, `name` FROM `node` WHERE `deleted` = 0 AND `class` = ? ORDER BY `left`";
      // $sql = "SELECT `id`, `parent_id`, `name` FROM `node` WHERE `deleted` = 0 AND `class` = ? AND `parent_id` IS NULL ORDER BY `left`";
      $params = array($class);
    } else {
      $sql = "SELECT `n1`.`id`, `n1`.`parent_id`, `n1`.`name` FROM `node` `n1`, `node` `n2` WHERE `n1`.`deleted` = 0 AND `n1`.`class` = ? AND `n1`.`left` >= `n2`.`left` AND `n1`.`right` <= `n2`.`right` AND `n2`.`id` = ?";
      $params = array($class, $parent_id);
    }

    $data = $db->getResultsK("id", $sql, $params);

    $output = array();
    self::indentList($data, $output, $parent_id);

    return $output;
  }

  private static function indentList(array &$data, array &$output, $parent_id, $indent = 0)
  {
    foreach ($data as $k => $v) {
      if ($v['parent_id'] == $parent_id) {
        $output[] = array($k, $v['name'], $indent);
        self::indentList($data, $output, $k, $indent + 1);
      }
    }
  }

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
    if ($this->deleted)
      return;

    $filter['class'] = $this->class;
    $filter['deleted'] = 0;
    $filter[$field] = $this->$field;

    if ($this->id)
      $filter['-id'] = $this->id;

    try {
      if (Node::count($filter, $this->getDB()))
        throw new DuplicateException($message ? $message : t('Такой объект уже существует.'));
    } catch (PDOException $e) { }
  }

  /**
   * Позволяет запретить редактирование полей конкретных типов.
   */
  public function canEditFields()
  {
    return true;
  }

  /**
   * Позволяет запретить привязку к разделам.
   */
  public function canEditSections()
  {
    return true;
  }

  /**
   * Позволяет объектам сериализовать дополнительные данные,
   * для примера см. класс FileNode.
   */
  public function getExtraXMLContent()
  {
    $result = '';
    foreach (Context::last()->registry->poll('ru.molinos.cms.node.xml', array($this)) as $tmp)
      if (!empty($tmp['result']))
        $result .= $tmp['result'];
    return $result;
  }

  public function getExtraPreviewXML(Context $ctx)
  {
  }

  /**
   * Возвращает данные для предварительного просмотра.
   */
  public function getPreviewXML(Context $ctx)
  {
    $result = '';
    $editable = $this->checkPermission('u');

    foreach ($this->getFormFields()->sort() as $name => $ctl) {
      if ($ctl->label) {
        if (false !== ($tmp = $ctl->preview($this))) {
          $editurl = ($editable and $ctl->isEditable($this))
            ? "admin/edit/{$this->id}/{$name}?destination=" . urlencode($_SERVER['REQUEST_URI'])
            : null;
          $result .= html::em('field', array(
            'name' => $name,
            'title' => $ctl->label,
            'editurl' => $editurl,
            'class' => get_class($ctl),
            ), $tmp);
        }
      }
    }

    foreach ($ctx->registry->poll('ru.molinos.cms.hook.preview.xml', array($this)) as $tmp)
      $result .= $tmp['result'];

    return $result;
  }

  /**
   * Сохранение объекта. Добавляет индексацию.
   */
  public function save()
  {
    if ($this->dirty) {
      $ctx = Context::last();

      // Публикация при создании, если есть права.
      // TODO: вынести в отдельный модуль?
      if ($this->isNew() and $this->checkPermission('p'))
        $this->data['published'] = true;

      $ctx->registry->broadcast('ru.molinos.cms.hook.node.before', array($ctx, $this, $this->isNew() ? 'create' : 'update'));
      $this->realSave();
      $ctx->registry->broadcast('ru.molinos.cms.hook.node', array($ctx, $this, $this->isNew() ? 'create' : 'update'));
    }

    return $this;
  }

  /**
   * Реальное сохранение объекта в БД.
   */
  private function realSave()
  {
    $this->data['lang'] = 'ru';
    $this->data['updated'] = gmdate('Y-m-d H:i:s');
    if (empty($this->data['created']))
      $this->data['created'] = $this->data['updated'];

    $data = $this->pack();

    if (null === $this->id)
      $this->saveNew($data);
    else
      $this->saveOld($data);

    foreach ($this->onsave as $query) {
      list($sql, $params) = $query;
      $sth = $this->getDB()->prepare($sql = str_replace('%ID%', intval($this->id), $sql));
      $sth->execute($params);

      if (defined('MCMS_FLOG_NODE_UPDATES')) {
        $tmp = array($sql);
        foreach ($params as $p)
          $tmp[] = var_export($p, true);

        mcms::flog("onSave[{$this->id}]: " . join('; ', $tmp));
      }
    }

    $this->onsave = array();
    $this->dirty = false;

    $this->updateXML();
  }

  /**
   * Упаковывает ноду для сохранения в БД.
   */
  private function pack()
  {
    $fields = $extra = array();

    foreach ($this->data as $k => $v) {
      // Числовые ключи игнорируем.
      if (is_numeric($k))
        continue;

      if (!empty($v)) {
        if ($v instanceof Node) {
          mcms::flog("pack[{$this->id}] » {$v->id}($v->name)");

          if (null === $v->id)
            $v->save();
          // Запрещаем ссылки на себя.
          if ($this->id == $v->id)
            continue;
          $this->onSave("DELETE FROM `node__rel` WHERE `tid` = %ID% AND `key` = ?", array($k));
          $this->onSave("REPLACE INTO `node__rel` (`tid`, `nid`, `key`) VALUES (%ID%, ?, ?)", array($v->id, $k));
        } elseif (self::isBasicField($k)) {
          $fields[$k] = $v;
        } elseif ('xml' == $k) {
        } else {
          $extra[$k] = $v;
        }
      } elseif ('published' == $k or 'deleted' == $k) {
        $fields[$k] = 0;
      }
    }

    $fields['data'] = serialize($extra);
    $fields['name_lc'] = Query::getSortName($this->name);

    return $fields;
  }

  /**
   * Создание новой ноды.
   */
  private function saveNew(array $data)
  {
    if (null !== $this->parent_id) {
      $position = $this->getDB()->getResult("SELECT `right` FROM `node` WHERE `id` = ?", array($this->parent_id));
      $max = intval($this->getDB()->getResult("SELECT MAX(`right`) FROM `node`"));

      // Превращаем простую ноду в родительску.
      if (null === $position) {
        $this->getDB()->exec("UPDATE `node` SET `left` = ?, `right` = ? WHERE `id` = ?", array($max, $max + 4, $this->parent_id));
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
        $this->getDB()->exec("UPDATE `node` SET `left` = `left` + ? WHERE `left` >= ?", array($delta + 2, $position));
        $this->getDB()->exec("UPDATE `node` SET `right` = `right` + ? WHERE `right` >= ?", array($delta + 2, $position));

        $this->getDB()->exec("UPDATE `node` SET `left` = `left` - ? WHERE `left` >= ?", array($delta, $position + 2));
        $this->getDB()->exec("UPDATE `node` SET `right` = `right` - ? WHERE `right` >= ?", array($delta, $position + 2));

        $data['left'] = $this->data['left'] = $position;
        $data['right'] = $this->data['right'] = $position + 1;
      }
    }

    list($sql, $params) = sql::getInsert('node', $data);
    $sth = $this->getDB()->prepare($sql);
    $sth->execute($params);
    $this->id = $this->getDB()->lastInsertId();
  }

  /**
   * Обновление существующей ноды.
   */
  private function saveOld(array $data)
  {
    list($sql, $params) = sql::getUpdate('node', $data, 'id');
    $sth = $this->getDB()->prepare($sql);
    $sth->execute($params);
  }

  /**
   * Добавление запроса в пост-обработку.
   */
  public function onSave($sql, array $params = null)
  {
    $this->onsave[] = array($sql, $params);
    return $this->touch();
  }

  /**
   * Удаление ноды.
   */
  public function delete()
  {
    $this->data['deleted'] = true;
    return $this->touch();
  }

  /**
   * Удаление из корзины.
   */
  public function erase()
  {
    if (empty($this->data['id']))
      throw new RuntimeException(t('Попытка удалить новый объект'));
    $this->getDB()->exec("DELETE FROM `node` WHERE `id` = ?", array($this->data['id']));
    $this->data['id'] = null;
    return $this->touch();
  }

  /**
   * Восстановление из корзины.
   */
  public function undelete()
  {
    $this->data['deleted'] = false;
    return $this->touch();
  }

  /**
   * Публикация ноды.
   */
  public function publish()
  {
    $this->data['published'] = true;
    return $this->touch();
  }

  /**
   * Сокрытие ноды.
   */
  public function unpublish()
  {
    $this->data['published'] = false;
    return $this->touch();
  }

  /**
   * Клонирование объекта.
   */
  public function duplicate($parent = null, $with_children = true)
  {
    if (!empty($this->data['id'])) {
      $id = $this->data['id'];

      $this->data['id'] = null;
      $this->data['published'] = false;
      $this->data['deleted'] = false;
      $this->data['created'] = null;

      // Даём возможность прикрепить клон к новому родителю.
      if (null !== $parent)
        $this->data['parent_id'] = $parent;

      $this->dirty = true;
      $this->save();

      $pdo = $this->getDB();
      $params = array(':new' => $this->data['id'], ':old' => $id);

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
            $c->duplicate($this->data['id']);
        }
        */
      }
    }

    return $this;
  }

  /**
   * Возвращает адрес списка документов этого типа.
   */
  public function getListURL()
  {
    return 'admin/content/list/' . $this->class;
  }

  /**
   * Возвращает true, если у объекта есть дети.
   */
  public function hasChildren()
  {
    if (empty($this->data['left']) or empty($this->data['right']))
      return false;
    if ($this->data['right'] - $this->data['left'] == 1)
      return false;
    $count = $this->getDB()->fetch("SELECT COUNT(*) FROM `node` WHERE `parent_id` = ? AND `deleted` = 0 AND `published` = 1", array($this->data['id']));
    return $count != 0;
  }

  /**
   * Возвращает XML текущего объекта со всеми детьми.
   */
  public function getTreeXML($published = true)
  {
    $xml = '';

    if ($this->data['id'] and $this->data['left'] and $this->data['right']) {
      mcms::flog("node[{$this->id}]: rebuilding XML tree");

      $mod = $published ? ' AND `published` = 1' : '';
      $children = $this->getDB()->getResultsKV("id", "xmltree", "SELECT `id`, `xmltree` FROM `node` WHERE `parent_id` = ? AND `deleted` = 0{$mod} ORDER BY `left`", array($this->data['id']));

      foreach ((array)$children as $nid => $xmltree) {
        if (null === $xmltree)
          $xmltree = Node::load($nid, $this->getDB())->getTreeXML($published);
        $xml .= $xmltree;
      }

      $xml = $this->getXML('node', $xml, false);
    }

    return $xml;
  }

  /**
   * Возвращает список стандартных полей объекта.
   */
  public static function getBasicFields()
  {
    return array(
      'id',
      'parent_id',
      'name',
      'name_lc',
      'lang',
      'class',
      'left',
      'right',
      'created',
      'updated',
      'published',
      'deleted',
      );
  }

  /**
   * Проверяет, является ли поле стандартным.
   */
  public static function isBasicField($fieldName)
  {
    return is_string($fieldName)
      ? in_array($fieldName, self::getBasicFields())
      : false;
  }

  /**
   * Формирование XML кода объекта.
   */
  public final function getXML($em = 'node', $extraContent = null)
  {
    $data = array();

    if (null !== $this->id and $this->getDB()) {
      mcms::flog("node[{$this->id}]: rebuilding XML ({$this->class},{$this->name})");

      $data = array(
        'id' => $this->id,
        '#text' => null,
        );

      if (empty($this->data['class']))
        throw new RuntimeException(t('Не удалось определить тип ноды.'));

      $schema = Schema::load($this->getDB(), $this->data['class']);
      $properties = array_unique(array_merge(self::getBasicFields(), $schema->getFieldNames()));

      foreach ($properties as $k) {
        if (empty($k) or in_array($k, array('xml', 'left', 'right')))
          continue;

        $v = $this->$k;

        if (self::isBasicField($k)) {
          $data[$k] = $v;
          continue;
        }

        if (isset($schema[$k])) {
          $data['#text'] .= $schema[$k]->format($v, $k);
          continue;
        }
      }

      $data['#text'] .= $this->getExtraXMLContent();
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
   * Сохраняет XML представление ноды в БД.
   */
  private function updateXML()
  {
    $this->getDB()->exec("UPDATE `node` SET `xml` = ? WHERE `id` = ?", array($this->getXML(), $this->id));
  }
};
