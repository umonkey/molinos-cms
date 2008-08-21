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
    return bebop_render_object("class", $this->class, "all", $this->data);
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
};
