<?php
/**
 * Виджет «облако тэгов».
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «облако тэгов».
 *
 * Формирует облако тэгов (общего для всего сайта), использованных документами
 * указанных в настройках виджета типов.  Например, можно построить отдельные
 * облака для статей, новостей и других типов, а можно построить одно общее.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class TagCloudWidget extends Widget implements iWidget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array описание виджета, ключи: name, description.
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Облако тэгов',
      'description' => 'Выводит список тэгов, содержащих доступные пользователю документы.',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * Форма позволяет выбрать типы документов, используемые для формирования
   * облака.
   *
   * @return Form вкладка с настройками виджета.
   */
  public static function getConfigOptions()
  {
    $types = array();

    foreach (Node::find(Context::last()->db, array('class' => 'type')) as $type)
      if (!in_array($type->name, TypeNode::getInternal()))
        $types[$type->name] = $type->title;

    return array(
      'linktpl' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон ссылки'),
        'default' => 'section/$id',
        ),
      'classes' => array(
        'type' => 'SetControl',
        'label' => t('Типы документов'),
        'options' => $types,
        ),
      );
  }

  /**
   * Препроцессор параметров.
   *
   * @param Context $ctx контекст запроса.
   *
   * @return array массив с параметрами виджета.
   */
  protected function getRequestOptions(Context $ctx)
  {
    $options = parent::getRequestOptions($ctx);
    $options['types'] = $this->classes;

    return $options;
  }

  /**
   * Обработка GET-запроса.
   *
   * Возвращает информацию о тэгах, используемых в облаке.  Для построения
   * самого облака используется шаблон.  Есть базовый шаблон (виджет может
   * работать «из коробки»).
   *
   * @param array $options параметры запроса.
   *
   * @return array информация о тэгах.  Ключ "tags" содержит массив с описаниями
   * отдельных тэгов, для каждого тэга возвращаются: id, name, cnt (количество
   * документов), percent (процент от общего количества документов).
   */
  public function onGet(array $options)
  {
    if (empty($options['types']))
      return null;
    $types = "'". join("', '", $options['types']) ."'";

    $data = $this->ctx->db->getResults($sql = 'SELECT n.id AS id, n.name AS name, '
      .'COUNT(*) AS `cnt` '
      .'FROM node n '
      .'INNER JOIN node__rel r ON r.tid = n.id '
      .'WHERE n.class = \'tag\' '
      .'AND n.published = 1 '
      .'AND n.deleted = 0 '
      .'AND r.nid IN (SELECT id FROM node WHERE published = 1 AND deleted = 0 AND class IN ('. $types .')) '
      .'GROUP BY n.id, n.name '
      .'ORDER BY n.name');

    // Calculate the total number of docs.
    $total = 0;
    foreach ($data as $k => $v)
      $total += $v['cnt'];

    // Подсчёт процентов и установка ссылок.
    foreach ($data as $k => $v) {
      $data[$k]['percent'] = intval(100 / $total * $v['cnt']);
      $data[$k]['link'] = str_replace('$id', $v['id'], $this->linktpl);
    }

    $output = '';
    foreach ($data as $tag)
      $output .= html::em('tag', $tag);

    return empty($output)
      ? null
      : html::em('tags', $output);
  }
}
