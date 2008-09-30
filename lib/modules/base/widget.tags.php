<?php
/**
 * Виджет «список разделов».
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «список разделов».
 *
 * Обычно используется для получения списка подразделов текущего раздела.
 * Стандартного шаблона нет, нужен специальный.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class TagsWidget extends Widget implements iWidget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array описание виджета, ключи: name, description.
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список разделов',
      'description' => 'Возвращает список разделов в виде дерева (переменная «$sections»).  Конкретный раздел, с которого будет начинаться дерево, может быть указан в адресной строке или жёстко задан ниже.',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * @return Form вкладка для настройки виджета.
   */
  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_fixed',
      'label' => t('Раздел по умолчанию'),
      'description' => t('Здесь можно выбрать раздел, который будет использован, если из адреса текущего запроса вытащить код раздела не удалось.'),
      'options' => array('page' => 'Из настроек страницы')
        + TagNode::getTags('select'),
      'default' => t('не используется'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_forcefixed',
      'label' => t('Всегда использовать этот раздел'),
      'description' => t('Всегда возвращать информацию о выбранном разделе, независимо от того, в каком разделе находится посетитель.'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_illcache',
      'label' => t('Используется для формирования меню'),
      )));

    return $form;
  }

  /**
   * Обработчик GET-запросов.
   *
   * @param array $options параметры запроса.
   *
   * @return array данные для шаблона, ключи: sections (вложенный список
   * подразделов), path (путь к текущему разделу).
   */
  public function onGet(array $options)
  {
    $result = array();

    if (!empty($options['root'])) {
      $root = Node::load($options['root']);

      $result['sections'] = $root->getChildren('nested');
      $result['path'] = array();
      $result['dynamic'] = $options['dynamic'];

      foreach ($root->getParents() as $node)
        $result['path'][] = $node->getRaw();
    }

    return $result;
  }

  private function tagsFilterPublished(array &$tree)
  {
    if (!empty($tree['children'])) {
      foreach ($tree['children'] as $k => $v) {
        if (empty($v['published']) or !empty($v['deleted']))
          unset($tree['children'][$k]);
        else
          $this->tagsFilterPublished($tree['children'][$k]);
      }
    }
  }

  /**
   * Препроцессор параметров.
   *
   * @param Context $ctx параметры запроса.
   *
   * @return array массив с параметрами виджета.
   */
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    if ($this->forcefixed) {
      if ('page' == ($option['root'] = $this->fixed))
        $options['root'] = $this->ctx->root->id;
    } else {
      $options['root'] = $ctx->section->id;
    }

    $options['dynamic'] = ($ctx->section->id !== null);

    if ($this->illcache)
      $options['anchor'] = $ctx->section->id;

    return $options;
  }
};
