<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Контекст, в котором выполняется запрос.  Содержит всю параметризацию.
class RequestContext
{
  // Элементы пути, ведущие к текущей странице.
  private $ppath;

  // Дополнительные элементы пути.
  private $apath;

  // Относящиеся к виджету параметры и файлы.
  private $get = null;
  private $post = null;
  private $files = null;

  // Текущий раздел и документ.
  private $section = null;
  private $document = null;

  // Основной раздел для страницы.
  private $root = null;

  // Шкура текущей страницы.
  private $theme = null;

  // Сохраняем глобальный контекст.
  private static $global = null;

  // Запрещаем создавать объекты напрямую.
  private function __construct()
  {
  }

  // Разгребаем параметры, подставляя текущие значения из пути
  // в локальные свойства $section и $document.
  private function setObjectIds(Node $page)
  {
    switch ($page->params) {
    case 'sec+doc':
      $this->document = empty($this->apath[1]) ? null : $this->apath[1];

    case 'sec':
      if (!empty($page->defaultsection) and is_numeric($page->defaultsection))
        $this->root = $page->defaultsection;

      if (null === ($this->section = empty($this->apath[0]) ? null : $this->apath[0]))
        $this->section = $this->root;

      break;

    case 'doc':
      $this->document = empty($this->apath[0]) ? null : $this->apath[0];
      break;
    }

    // Нормализуем идентификаторы.

    if (null !== $this->document and !is_numeric($this->document))
      if (null === ($this->document = mcms::db()->getResult("SELECT `id` FROM `node` WHERE `code` = :code", array(':code' => $this->document))))
          throw new PageNotFoundException();

    if (null !== $this->section and !is_numeric($this->section))
      if (null === ($this->section = mcms::db()->getResult("SELECT `id` FROM `node` WHERE `code` = :code", array(':code' => $this->section))))
        throw new PageNotFoundException();
  }

  // Запрещаем изменять свойства извне.
  public function __set($key, $value)
  {
    throw new InvalidArgumentException("RequestContext is a read-only object.");
  }

  public function __isset($key)
  {
    switch ($key) {
    case 'ppath':
    case 'apath':
    case 'get':
    case 'post':
    case 'files':
    case 'root':
    case 'section':
    case 'document':
    case 'theme':
      return isset($this->$key);

    case 'section_id':
      return !empty($this->section);

    case 'document':
      return !empty($this->document);

    default:
      return false;
    }
  }

  public function __get($key)
  {
    switch ($key) {
      case 'ppath':
      case 'apath':
      case 'get':
      case 'post':
      case 'files':
        if ($this->$key === null)
          return array();
        return $this->$key;

      case 'theme':
        return $this->theme;

      case 'root':
        return empty($this->root) ? null : $this->root;

      case 'section':
      case 'document':
        $node = &$this->$key;

        if ($node === null)
          return null;
        elseif (is_object($node))
          return $this->$key;
        else {
          $tmp = Node::load($node);

          if ($tmp->class == 'tag' and $key != 'section')
            return null;

          return $tmp;
        }

      case 'section_id':
        return $this->section;

      case 'document_id':
        return $this->document;

      default:
        throw new InvalidArgumentException("Unknown key for RequestContext: ". $key);
    }
  }

  // Упрощённое обращение к параметрам $_GET.
  public function get($key, $default = null)
  {
    if ($this->get !== null and array_key_exists($key, $this->get))
      return $this->get[$key];
    return $default;
  }

  // Упрощённое обращение к параметрам $_POST.
  public function post($key, $default = null)
  {
    if ($this->post !== null and array_key_exists($key, $this->post))
      return $this->post[$key];
    return $default;
  }

  // Формирует глобальный контекст.
  public static function setGlobal(array $ppath = null, array $apath = null, Node $page = null)
  {
    if (self::$global !== null)
      throw new InvalidArgumentException("There is a global request context already.");

    $ctx = new RequestContext();

    $ctx->ppath = $ppath;
    $ctx->apath = $apath;

    if (null !== $page) {
      $ctx->theme = $page->theme;
      $ctx->setObjectIds($page);
    }

    return self::$global = $ctx;
  }

  // Возвращает текущий контекст.
  public static function getGlobal()
  {
    if (self::$global === null)
      throw new InvalidArgumentException("There is no global request context yet.");

    return self::$global;
  }

  // Формирует контекст для виджета, из глобального.
  public static function getWidget(array $get, array $post = null, array $files = null)
  {
    if (null === self::$global)
      $ctx = new RequestContext();
    else
      $ctx = clone(self::getGlobal());

    $ctx->get = $get;
    $ctx->post = $post;
    $ctx->files = $files;

    return $ctx;
  }
};
