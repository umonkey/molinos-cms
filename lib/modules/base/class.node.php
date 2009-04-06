<?php
/**
 * Базовый класс для всех объектов, хранимых в БД.
 *
 * Реализует функции, общие для всех объектов, но не имеющие отношения к
 * взаимодействию с БД — этот код вынесен в класс NodeBase.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Базовый класс для всех объектов, хранимых в БД.
 *
 * Реализует функции, общие для всех объектов, но не имеющие отношения к
 * взаимодействию с БД — этот код вынесен в класс NodeBase.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class Node
{
  private $stub;
  private $isnew = true;
  private $onsave = array();

  public function __construct(NodeStub $stub)
  {
    $this->stub = $stub;
    if ($this->id)
      $this->isnew = false;
  }

  private final function __get($key)
  {
    return $this->stub->$key;
  }

  private final function __set($key, $value)
  {
    $this->stub->$key = $value;
  }

  private final function __isset($key)
  {
    return isset($this->stub->$key);
  }

  private final function __unset($key)
  {
    unset($this->stub->$key);
  }

  /**
   * Проверяет, изменялся ли объект.
   */
  public function isNew()
  {
    return $this->isnew;
  }

  /**
   * Создание новой ноды нужного типа.
   */
  public static function create($class, array $data = array())
  {
    $stub = NodeStub::create(null, Context::last()->db);

    $data['class'] = $class;
    foreach ($data as $k => $v)
      $stub->$k = $v;

    return $stub->getObject();
  }

  /**
   * Загрузка конкретной ноды.
   */
  public static function load($id, PDO $db = null)
  {
    if (null === $db)
      $db = Context::last()->db;

    if (is_array($id)) {
      if (!is_array($nodes = Node::find($db, $id)) or empty($nodes))
        throw new ObjectNotFoundException();
      return array_shift($nodes);
    } elseif (!is_numeric($id)) {
      throw new InvalidArgumentException(t('Идентификатор загружаемой ноды должен быть числовым.'));
    }

    return NodeStub::create($id, $db)->getObject();
  }

  /**
   * Поиск нод.
   */
  public static function find(PDO_Singleton $db, $query, $limit = null, $offset = null)
  {
    if ($query instanceof Query)
      ;
    elseif (is_array($query))
      $query = new Query($query);
    else
      throw new InvalidArgumentException(t('Запрос должен быть описан массивом или объектом Query.'));

    list($sql, $params) = $query->getSelect($limit, $offset, '*');

    $result = array();

    $sth = $db->prepare($sql);
    $sth->execute($params);

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      $id = $row['id'];
      unset($row['id']);
      $result[$id] = NodeStub::create($id, $db, $row);
    }

    return $result;
  }

  /**
   * Поиск нод, результат — в XML.
   */
  public static function findXML(PDO_Singleton $db, $query, $em = null)
  {
    $output = '';

    if ('node' == $em)
      $em = null;

    if (is_array($query))
      $query = new Query($query);

    list($sql, $params) = $query->getSelectXML();

    try {
      for ($sth = $db->exec($sql, $params); $xml = $sth->fetchColumn(0); ) {
        if (null !== $em)
          $xml = '<' . $em . substr($xml, 5, -5) . $em . '>';
        $output .= $xml;
      }
    } catch (PDOException $e) {
      mcms::flog('database needs an upgrade');
    }

    return $output;
  }

  /**
   * Подсчёт нод.
   */
  public static function count(PDO_Singleton $db, array $query)
  {
    $query = new Query($query);
    list($sql, $params) = $query->getCount();

    $sth = $db->prepare($sql);
    $sth->execute($params);

    return intval($sth->fetchColumn(0));
  }

  /**
   * Проверка доступа.
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
   * Получение формы.
   */
  public function formGet()
  {
    if (!$this->checkPermission($this->id ? 'u' : 'c'))
      throw new ForbiddenException(t('У вас недостаточно прав для работы с этим документом.'));

    $form = $this->getFormFields()->getForm(array(
      'action' => $this->getFormAction(),
      'title' => $this->getFormTitle(),
      ));

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
   * Форвардим вызовы в стаб.
   */
  private function __call($method, $args)
  {
    if (!method_exists($this->stub, $method))
      throw new RuntimeException(t('Метод %class::%method() не существует.', array(
        '%class' => __CLASS__,
        '%method' => $method,
        )));
    $result = call_user_func_array(array($this->stub, $method), $args);
    return $result;
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
  public function formProcess(array $data)
  {
    if (!$this->checkPermission($this->id ? 'u' : 'c'))
      throw new ForbiddenException(t('Ваших полномочий недостаточно '
        .'для редактирования этого объекта.'));

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

    if ($this->checkPermission('u'))
      $links['edit'] = array(
        'href' => '?q=admin/edit/'. $this->id
          .'&destination=CURRENT',
        'title' => t('Редактировать'),
        'icon' => 'edit',
        );

    if ($this->checkPermission('c'))
      $links['clone'] = array(
        'href' => '?q=nodeapi.rpc&action=clone&node='. $this->id
          .'&destination=CURRENT',
        'title' => t('Клонировать'),
        'icon' => 'clone',
        );

    if ($this->checkPermission('d'))
      $links['delete'] = array(
        'href' => '?q=nodeapi.rpc&action=delete&node='. $this->id
          .'&destination=CURRENT',
        'title' => t('Удалить'),
        'icon' => 'delete',
        );

    if ($this->checkPermission('p') and !in_array($this->class, array('type')) and !$this->deleted) {
      if ($this->published) {
        $action = 'unpublish';
        $title = 'Скрыть';
      } else {
        $action = 'publish';
        $title = 'Опубликовать';
      }

      $links['publish'] = array(
        'href' => '?q=nodeapi.rpc&action='. $action .'&node='. $this->id
          .'&destination=CURRENT',
        'title' => t($title),
        'icon' => $action,
        );
    }

    if ($this->published and !$this->deleted and !in_array($this->class, array('domain', 'widget', 'type')))
      $links['locate'] = array(
        'href' => '?q=nodeapi.rpc&action=locate&node='. $this->id,
        'title' => t('Найти на сайте'),
        'icon' => 'locate',
        );

    if (($ctx = Context::last()) and $ctx->canDebug())
      $links['dump'] = array(
        'href' => '?q=nodeapi.rpc&action=dump&node='. $this->id
          . '&rev='. $this->rid,
        'title' => 'Содержимое объекта',
        'icon' => 'dump',
        );

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
      foreach ((array)Node::find(Context::last()->db, array('class' => $class, 'deleted' => 0)) as $n) {
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

  public function getName()
  {
    return $this->name;
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
    $filter['class'] = $this->class;
    $filter['deleted'] = 0;
    $filter[$field] = $this->$field;

    if ($this->id)
      $filter['-id'] = $this->id;

    try {
      if (Node::count($this->getDB(), $filter))
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
  }

  /**
   * Сохранение объекта. Добавляет индексацию.
   */
  public function save()
  {
    $ctx = Context::last();
    $user = $ctx->user;

    if (!$this->uid and $user->id and $user->id != $this->id)
      $this->uid = $user->getNode();

    $ctx->registry->broadcast('ru.molinos.cms.hook.node.before', array($ctx, $this, $this->isNew() ? 'create' : 'update'));

    $this->stub->save();

    if ($this->checkPermission('p'))
      $this->stub->publish();

    if (count($indexes = $this->getSchema()->getIndexes())) {
      $db = $this->stub->getDB();

      foreach ($indexes as $idx) {
        $data = array('id' => $this->id);
        $table = 'node__idx_' . $idx;

        list($sql, $params) = sql::getDelete($table, $data);
        $db->exec($sql, $params);

        foreach ($indexes as $idx)
          $data['value'] = $this->$idx;
        list($sql, $params) = sql::getInsert($table, $data);
        $db->exec($sql, $params);
      }
    }

    $ctx->registry->broadcast('ru.molinos.cms.hook.node', array($ctx, $this, $this->isNew() ? 'create' : 'update'));

    return $this;
  }

  public function getListURL()
  {
    $url = empty($_SERVER['HTTP_REFERER'])
      ? 'admin/content/list'
      : $_SERVER['HTTP_REFERER'];
    return $url;
  }

  public function hasChildren()
  {
    if (empty($this->left) or empty($this->right))
      return false;
    if ($this->right - $this->left == 1)
      return false;
    $count = $this->getDB()->fetch("SELECT COUNT(*) FROM `node` WHERE `parent_id` = ? AND `deleted` = 0 AND `published` = 1", array($this->id));
    return $count != 0;
  }
};
