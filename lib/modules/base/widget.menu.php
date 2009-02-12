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
  public static function getConfigOptions()
  {
    $fields = array();
    $schema = Schema::load('tag');

    foreach ($schema as $k => $v)
      if ($v instanceof URLControl)
        $fields[$k] = $v->label;

    asort($fields);

    $tags = array(
      'anything' => t('Текущий, если в нём пусто — родительский'),
      'parent' => t('Родительский (соседние разделы)'),
      'root' => t('Из настроек страницы'),
      );

    foreach (TagNode::getTags('select') as $k => $v)
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
      'prefix' => array(
        'type' => 'TextLineControl',
        'label' => t('Префикс для ссылок'),
        'description' => t('Обычно это поле оставляют пустым, и ссылки в меню получаются относительными (относительно значения тэга &lt;base/&gt;, например).&nbsp; Если вам нужно сделать так, чтобы ссылки всгда были относительными для корня сайта &mdash; введите здесь &laquo;/&raquo;.&nbsp; Можно использовать и что-нибудь более оригинальное.'),
        ),
      'hidecurrent' => array(
        'type' => 'BoolControl',
        'label' => t('Убирать ссылку с текущего элемента'),
        'description' => t('Все классы проставляются для элементов li и ul, поэтому на стилизацию меню эта настройка не влияет, но повышает рейтинг сайта в глазах педантичных критиков.'),
        ),
      'external' => array(
        'type' => 'EnumControl',
        'label' => t('Предпочитать ссылку из поля'),
        'options' => $fields,
        'default_label' => t('(не предпочитать)'),
        ),
      'header' => array(
        'type' => 'EnumControl',
        'label' => t('Заголовок меню'),
        'options' => array(
          'h2' => t('Имя виджета, H2'),
          'h3' => t('Имя виджета, H3'),
          'h4' => t('Имя виджета, H4'),
          ),
        'default_label' => t('(не выводить)'),
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
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    if ('root' == $this->fixed)
      $options['root'] = $ctx->root_id;
    elseif ('parent' == $this->fixed)
      $options['root'] = $ctx->section->parent_id;
    elseif (is_numeric($this->fixed))
      $options['root'] = $this->fixed;
    else
      $options['root'] = $ctx->section_id;

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
      $root = $toplevel = Node::load(array('class' => 'tag', 'parent_id' => null));
    } elseif ($options['root'] instanceof Node) {
      $root = $options['root'];
    } else {
      $root = Node::load($options['root']);
    }

    if (!($root instanceof TagNode))
      throw new RuntimeException(t('MenuWidget получил «%class», а не раздел.', array(
        '%class' => $root->class,
        )));

    return $root->getTreeXML('section');
  }
};
