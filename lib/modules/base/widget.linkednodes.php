<?php
/**
 * Виджет «связанные документы».
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «связанные документы».
 *
 * Возвращает список документов определённого типа, привязанных к текущему
 * документу.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class LinkedNodesWidget extends Widget
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
      'name' => 'Связанные документы',
      'description' => 'Выводит список документов, привязанных к текущему.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/LinkedNodesWidget',
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
    $types = Node::getSortedList('type', 'title', 'name');

    return array(
      'hosts' => array(
        'type' => 'SetControl',
        'label' => t('Возвращать только для'),
        'options' => $types,
        ),
      'classes' => array(
        'type' => 'SetControl',
        'label' => t('Возвращать только следующие типы'),
        'options' => $types,
        ),
      'field' => array(
        'type' => 'TextLineControl',
        'label' => t('Привязка к полю'),
        ),
      'sort' => array(
        'type' => 'EnumControl',
        'label' => t('Сортировка'),
        'options' => array(
          'name' => t('По имени'),
          'created' => t('По дате добавления (сначала старые)'),
          '-created' => t('По дате добавления (сначала новые)'),
          '-id' => t('По идентификатору'),
          ),
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

    if (empty($ctx->document->id))
      return false;

    if (!in_array($ctx->document->class, $this->hosts))
      return false;

    $options['doc'] = $ctx->document->id;
    $options['classes'] = $this->classes;
    $options['field'] = $this->field;
    $options['sort'] = $this->sort;

    return $options;
  }

  /**
   * Обработка GET-запроса.
   */
  public function onGet(array $options)
  {
    $nodes = Node::findXML($this->ctx->db, array(
      'class' => $options['classes'],
      'tagged' => $options['doc'],
      'published' => 1,
      'deleted' => 0,
      '#sort' => $options['sort'],
      ));

    return $nodes;
  }
}
