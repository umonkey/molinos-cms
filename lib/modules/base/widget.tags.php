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
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
      'name' => 'Список разделов',
      'description' => 'Выводит список разделов и подразделов в виде дерева.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/TagsWidget',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * @return Form вкладка для настройки виджета.
   */
  public static function getConfigOptions()
  {
    return array(
      'fixed' => array(
        'type' => 'EnumControl',
        'label' => t('Раздел по умолчанию'),
        'description' => t('Здесь можно выбрать раздел, который будет использован, если из адреса текущего запроса вытащить код раздела не удалось.'),
        'options' => array('page' => 'Из настроек страницы')
          + Node::getSortedList('tag'),
        'default' => t('не используется'),
        ),
      'forcefixed' => array(
        'type' => 'BoolControl',
        'label' => t('Всегда использовать этот раздел'),
        'description' => t('Всегда возвращать информацию о выбранном разделе, независимо от того, в каком разделе находится посетитель.'),
        ),
      'illcache' => array(
        'type' => 'BoolControl',
        'label' => t('Используется для формирования меню'),
        ),
      'lowmemory' => array(
        'type' => 'BoolControl',
        'label' => t('Не подгружать файлы (экономит память)'),
        ),
      );
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
      if (is_numeric($root = $options['root']))
        $root = Node::load($root);

      if ($this->lowmemory)
        $root->loadChildren(null, true);

      $result['sections'] = $root->getChildren('nested');

      $result['path'] = array();
      $result['dynamic'] = $options['dynamic'];

      self::tagsFilterPublished($result['sections']);

      if ($root->id)
        foreach ($root->getParents() as $node)
          $result['path'][] = $node->getRaw();
    }

    return $result;
  }

  public static function tagsFilterPublished(array &$tree)
  {
    if (!empty($tree['children'])) {
      foreach ($tree['children'] as $k => $v) {
        if (empty($v['published']) or !empty($v['deleted']))
          unset($tree['children'][$k]);
        else
          self::tagsFilterPublished($tree['children'][$k]);
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
      if ('page' == ($options['root'] = $this->fixed))
        $options['root'] = $this->ctx->root;
    } else {
      $options['root'] = $ctx->section;
    }

    $options['dynamic'] = ($ctx->section->id !== null);

    if ($this->illcache)
      $options['anchor'] = $ctx->section->id;

    if (!empty($options['root']) and !is_numeric($options['root']) and !is_object($options['root']))
      throw new InvalidArgumentException(t('Вместо кода раздела в виджет %name пришёл какой-то мусор: %trash.', array(
        '%name' => $this->name,
        '%trash' => $options['root'],
        )));

    return $options;
  }
};
