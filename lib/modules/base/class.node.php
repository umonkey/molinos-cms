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
      $theme = Context::last()->locateDomain()->theme;
    if (null === $data)
      $data = $this->data;
    return bebop_render_object("type", $this->class, $theme, $data);
  }

  /**
   * Возвращает данные для формы редактирования объекта.
   *
   * @return array данные для формы, включая массивы: node_access — описание
   * доступа к объекту, reset_access — флаг сброса доступа, node_published —
   * состояние публикации (FIXME: зачем?)
   */
  public function formGetData()
  {
    $user = mcms::user();

    $data = parent::formGetData();

    if ($user->hasAccess('u', 'user'))
      $data['node_access'] = $this->getAccess();

    $data['reset_access'] = 1;
    $data['node_published'] = $this->published;

    return $data;
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

    $user = mcms::user();

    if (!empty($data['reset_access'])) {
      if ($user->hasAccess('u', 'user'))
        $this->setAccess(empty($data['node_access']) ? array() : $data['node_access']);
    }

    return $res;
  }

  /**
   * Возвращает базовое описание объекта.
   *
   * @return array структура объекта.  Используется как основа для всех
   * добавляемых пользователем типов.
   */
  public function getDefaultSchema()
  {
    return array(
      'title' => 'Без названия',
      'lang' => 'ru',
      'fields' => array(
        'name' => array(
          'label' => t('Заголовок'),
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'created' => array(
          'label' => t('Дата создания'),
          'type' => 'DateTimeControl',
          'required' => false,
          ),
        'uid' => array(
          'label' => t('Автор'),
          'type' => 'NodeLinkControl',
          'required' => false,
          'values' => 'user.name',
          ),
        ),
      );
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

    if (bebop_is_debugger())
      $links['dump'] = array(
        'href' => '?q=nodeapi.rpc&action=dump&node='. $this->id,
        'title' => 'Содержимое объекта',
        'icon' => 'dump',
        );

    return $links;
  }

  public static function getSortedList($class, $field = 'name', $key = 'id')
  {
    $result = array();

    foreach (Node::find(array('class' => $class)) as $n)
      $result[$n->$key] = $n->$field;

    asort($result);

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
};
