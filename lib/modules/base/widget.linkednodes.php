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
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Связанные документы',
      'description' => 'Выводит список документов, привязанных к текущему.',
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
  public static function formGetConfig()
  {
    $types = Node::getSortedList('type', 'title', 'name');

    $form = parent::formGetConfig();

    $form->addControl(new SetControl(array(
      'value' => 'config_hosts',
      'label' => t('Возвращать только для'),
      'options' => $types,
      )));

    $form->addControl(new SetControl(array(
      'value' => 'config_classes',
      'label' => t('Возвращать только следующие типы'),
      'options' => $types,
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_field',
      'label' => t('Привязка к полю'),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_sort',
      'label' => t('Сортировка'),
      'options' => array(
        'name' => t('По имени'),
        'created' => t('По дате добавления (сначала старые)'),
        '-created' => t('По дате добавления (сначала новые)'),
        '-id' => t('По идентификатору'),
        ),
      )));

    return $form;
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

    $options['classes'] = $this->classes;
    $options['field'] = $this->field;

    return $options;
  }

  /**
   * Обработка GET-запроса.
   */
  public function onGet(array $options)
  {
    $filter = array(
      'class' => $options['classes'],
      'tagged' => $this->ctx->document->id,
      'published' => true,
      '#raw' => true,
      );

    if (!empty($this->sort))
      $filter['#sort'] = $this->sort;

    $result = array(
      'documents' => Node::find($filter),
      );

    return $result;
  }
}
