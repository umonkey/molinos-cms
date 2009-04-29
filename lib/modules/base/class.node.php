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
class Node extends NodeBase implements iContentType
{
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
    if (null === $theme)
      $theme = Context::last()->theme;
    if (null === $data)
      $data = $this->data;
    return template::render($theme, 'type', $this->class, $data);
  }

  /**
   * Обработка форм.
   *
   * Вызывается из nodeapi.rpc, в дополнение к родительским действиям
   * обрабатывает изменения в правах доступа.
   *
   * @return mixed см. NodeBase::formProcess()
   */
  public function formProcess(array $data)
  {
    if (!$this->checkPermission($this->id ? 'u' : 'c'))
      throw new ForbiddenException(t('Ваших полномочий недостаточно '
        .'для редактирования этого объекта.'));

    $res = parent::formProcess($data);

    return $res;
  }

  public function getActionLinks()
  {
    $links = array();

    $adminui = (false !== strpos($_SERVER['REQUEST_URI'], 'admin/'));

    if ($this->checkPermission('u'))
      $links['edit'] = array(
        'href' => '?q=admin/content/edit/'. $this->id
          .'&destination=CURRENT',
        'title' => t('Редактировать'),
        'icon' => 'edit',
        );

    if ($adminui and $this->checkPermission('c'))
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

    if ($adminui)
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

  public static function getSortedList($class, $field = 'name', $key = 'id', $showname = true)
  {
    $result = array();

    // Вывод дерева страниц и разделов
    if (in_array($class, array('tag', 'domain'))) {
      $roots = Node::find(array(
        'class' => $class,
        'parent_id' => null,
        ));

      foreach ($roots as $root) {
        foreach ($root->getChildren('flat') as $em)
          $result[$em['id']] = str_repeat('&nbsp;', 2 * $em['depth']) . trim($em['name']);
      }
    }

    // Вывод обычных списков
    else {
      foreach (Node::find(array('class' => $class, 'deleted' => 0)) as $n) {
        $result[$n->$key] = trim(('name' == $field)
          ? $n->getName()
          : $n->$field);

        if ($showname and 'name' != $field)
          $result[$n->$key] .= ' (' . $n->name . ')';
      }

      asort($result);
    }

    return $result;
  }

  public static function _id($node)
  {
    if (is_object($node))
      return $node->id;
    elseif (is_array($node))
      return $node['id'];
    else
      return $node;
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
    $allowed = mcms::db()->getResultsV("id", "SELECT id FROM node WHERE class = 'tag' AND deleted = 0 AND id IN "
      . "(SELECT tid FROM node__rel WHERE nid IN "
      . "(SELECT n.id FROM node n INNER JOIN node__rev v ON v.rid = n.rid "
      . "WHERE n.deleted = 0 AND n.class = 'type' AND v.name = ?))",
      array($this->class));

    if (null === ($permitted = mcms::user()->getPermittedSections()))
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

    $img = call_user_func($func, mcms::config('filestorage') . DIRECTORY_SEPARATOR . $this->filepath);

    if (null === $img)
      throw new RuntimeException(t('Не удалось открыть файл %name (возможно, он повреждён).', array(
        '%name' => $this->filename,
        )));

    return $img;
  }

  /**
   * Возвращает массив с отформатированными полями документа.
   */
  public function format()
  {
    $result = array();
    $schema = $this->getSchema();

    foreach ($this->getRaw() as $k => $v)
      $result[$k] = isset($schema[$k])
        ? $schema[$k]->format($v)
        : $v;

    return $result;
  }
};
