<?php
/**
 * Виджет «меню».
 *
 * Формирует вложенные HTML-списки в соответствии с картой разделов.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «меню».
 *
 * Формирует вложенные HTML-списки в соответствии с картой разделов.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class MenuWidget extends Widget implements iWidget
{
  private $options;

  /**
   * Возвращает описание виджета.
   *
   * @return array массив с ключами: name, description.
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Меню',
      'description' => 'Выводит информацию о разделах в виде ненумерованного списка.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/MenuWidget',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * @return Form вкладка для настройки виджета.
   */
  public static function getConfigOptions(Context $ctx)
  {
    $fields = array();
    $schema = Schema::load($ctx->db, 'tag');

    foreach ($schema as $k => $v)
      if ($v instanceof URLControl)
        $fields[$k] = $v->label;

    asort($fields);

    $tags = array(
      'anything' => t('Текущий, если в нём пусто — родительский'),
      'parent' => t('Родительский (соседние разделы)'),
      'root' => t('Из настроек страницы'),
      );

    foreach (Node::getSortedList('tag') as $k => $v)
      $tags[$k] = $v;

    return array(
      'fixed' => array(
        'type' => 'EnumControl',
        'label' => t('Всегда возвращать раздел'),
        'description' => t("По умолчанию виджет возвращает информацию о текущем разделе.&nbsp; Вы можете настроить его на фиксированный раздел.&nbsp; Подсветка текущего раздела при этом сохранится."),
        'options' => $tags,
        'default' => t('Текущий (его подразделы)'),
        ),
      'depth' => array(
        'type' => 'NumberControl',
        'label' => t('Глубина'),
        'description' => t("Меню будет содержать столько уровней вложенности."),
        ),
      );
  }

  /**
   * Препроцессор параметров.
   *
   * @param Context $ctx контекст запроса.
   *
   * @return array выбранные из контекста параметры, относящиеся к виджету.
   */
  protected function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);
    $options['root'] = $params['section']['id'];

    switch ($this->fixed) {
    case 'root':
      $options['root'] = $params['root']['id'];
      break;
    case 'parent':
      $options['parent'] = true;
      break;
    default:
      if (is_numeric($this->fixed))
        $options['root'] = $this->fixed;
    }

    return $this->options = $options;
  }

  /**
   * Обработка GET запросов.
   *
   * @param array $options параметры запроса.
   *
   * @return array результат работы, содрежит одно значение: "html", с готовым
   * кодом меню.
   */
  public function onGet(array $options)
  {
    $toplevel = null;

    // Загружаем текущий (или корневой) раздел.
    if (empty($options['root'])) {
      $root = $toplevel = Node::load(array('class' => 'tag', 'parent_id' => null, 'deleted' => 0));
    } elseif ($options['root'] instanceof Node) {
      $root = $options['root'];
    } else {
      $root = Node::load($options['root']);
    }

    $root = $this->checkNeedParent($root);

    if ('tag' != $root->class)
      throw new RuntimeException(t('MenuWidget получил «%class», а не раздел.', array(
        '%class' => $root->class,
        )));

    return $root->getTreeXML('section');
  }

  private function checkNeedParent($root)
  {
    if ('anything' == $this->fixed and $root->parent_id and !$root->hasChildren())
      $root = Node::load($root->parent_id)->getObject();

    return $root;
  }
};
