<?php

class DictWidget extends Widget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array массив с описанием виджета, ключи: name, description.
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Содержимое справочника',
      'description' => 'Выводит все элементы справочника, например, для построения навигационных блоков или параметрического поиска.',
      );
  }

  public static function getConfigOptions(Context $ctx)
  {
    $list = TypeNode::getDictionaries();

    if (empty($list))
      throw new ForbiddenException(t('Не определён ни один справочник.'));

    return array(
      'type' => array(
        'type' => 'EnumControl',
        'label' => t('Справочник'),
        'required' => true,
        'options' => $list,
        ),
      );
  }

  public function onGet(array $options)
  {
    return Node::findXML($this->ctx->db, array(
      'class' => $this->type,
      'deleted' => 0,
      'published' => 1,
      '#sort' => 'name',
      ));
  }
}
