<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MenuWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Меню',
      'description' => 'Возвращает информацию о разделах в виде ненумерованного списка.',
      );
  }

  public static function formGetConfig()
  {
    $fields = array();

    $schema = TypeNode::getSchema('tag');

    foreach ($schema['fields'] as $k => $v)
      if ($v['type'] == 'URLControl')
        $fields[$k] = $v['label'];

    asort($fields);

    $tags = array(
      'anything' => t('Текущий, если в нём пусто — родительский'),
      'parent' => t('Родительский (соседние разделы)'),
      'root' => t('Основной для страницы (или домена)'),
      );

    foreach (TagNode::getTags('select') as $k => $v)
      $tags[$k] = $v;

    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_fixed',
      'label' => t('Всегда возвращать раздел'),
      'description' => t("По умолчанию виджет возвращает информацию о текущем разделе.&nbsp; Вы можете настроить его на фиксированный раздел.&nbsp; Подсветка текущего раздела при этом сохранится."),
      'options' => $tags,
      'default' => t('Текущий (его подразделы)'),
      )));

    $form->addControl(new NumberControl(array(
      'value' => 'config_depth',
      'label' => t('Глубина'),
      'description' => t("Меню будет содержать столько уровней вложенности."),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_prefix',
      'label' => t('Префикс для ссылок'),
      'description' => t('Обычно это поле оставляют пустым, и ссылки в меню получаются относительными (относительно значения тэга &lt;base/&gt;, например).&nbsp; Если вам нужно сделать так, чтобы ссылки всгда были относительными для корня сайта &mdash; введите здесь &laquo;/&raquo;.&nbsp; Можно использовать и что-нибудь более оригинальное.'),
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_hidecurrent',
      'label' => t('Убирать ссылку с текущего элемента'),
      'description' => t('Все классы проставляются для элементов li и ul, поэтому на стилизацию меню эта настройка не влияет, но повышает рейтинг сайта в глазах педантичных критиков.'),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_external',
      'label' => t('Предпочитать ссылку из поля'),
      'options' => $fields,
      'default' => t('(не предпочитать)'),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_header',
      'label' => t('Заголовок меню'),
      'options' => array(
        'h2' => t('Имя виджета, H2'),
        'h3' => t('Имя виджета, H3'),
        'h4' => t('Имя виджета, H4'),
        ),
      'default' => t('Не выводить'),
      )));

    return $form;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    if ('root' == $this->fixed)
      $options['root'] = $ctx->root;
    elseif ('parent' == $this->fixed)
      $options['root'] = $ctx->section->parent_id;
    elseif (is_numeric($this->fixed))
      $options['root'] = $this->fixed;
    else
      $options['root'] = $ctx->section->id;

    $options['current'] = $ctx->section_id;
    $options['document'] = $ctx->document_id;

    return $this->options = $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $toplevel = null;

    // Загружаем текущий (или корневой) раздел.
    if (empty($options['root'])) {
      $root = $toplevel = Node::load(array('class' => 'tag', 'parent_id' => null));
    } else {
      $root = Node::load($options['root']);
    }

    // Определяем путь к текущему разделу.
    if (null === $this->ctx->section_id)
      $path = array();
    elseif (!is_array($path = $this->ctx->section->getParents()))
      $path = array();

    // Загружаем детей.
    $root->loadChildren();

    if (!self::countChildren($root) and ('anything' == $this->fixed)) {
      $root = Node::load($root->parent_id);
      $root->loadChildren();
    }

    if ($this->ctx->section_id)
      $current = $this->ctx->section->id;
    elseif (null !== $toplevel)
      $current = $toplevel->id;
    else
      $current = null;

    $output = $this->renderMenu($root, $this->depth ? $this->depth : 1, $path, $current);

    if (!empty($output)) {
      if (in_array($this->header, array('h2', 'h3', 'h4')))
        $output = '<h2>'. mcms_plain($this->me->title) .'</h2>'. $output;

      $output = "<div class='widget-menu-{$this->me->name}'>{$output}</div>";
    }

    return array('html' => $output);
  }

  private function renderMenu(TagNode $root, $depth, array $path, $myid)
  {
    $output = '';
    $ndepth = $depth - 1;
    $level = $this->depth - $depth + 1;

    if  (!empty($root->children)) {
      $submenu = '';

      foreach ($root->children as $idx => $child) {
        if (empty($child->published))
          continue;

        if (!empty($child->hidden))
          continue;

        $li = $a = array();

        if (array_key_exists($child->id, $path))
          $li['class'][] = 'active';

        if ($myid == $child->id)
          $li['class'][] = 'current';

        if ($idx == 0)
          $li['class'][] = 'first';
        elseif ($idx == count($root->children) - 1)
          $li['class'][] = 'last';

        $li['class'][] = 'level-'. $level;

        if (!empty($child->description))
          $a['title'] = mcms_plain($child->description);

        if (null !== $this->external and !empty($child->{$this->external}))
          $link = $child->{$this->external};
        else
          $link = $this->prefix . $child->id;

        $a['href'] = str_replace('$tid', $child->id, $link);

        if ($this->hidecurrent and in_array('current', $li['class'])) {
          if (empty($this->options['document'])) {
            $a['href'] = null;
            $a['class'][] = 'nolink';
          }
        }

        $submenu .= mcms::html('li', $li, mcms::html('a', $a, mcms_plain($child->name)));

        // Отрезаем финальный </li>.
        $submenu = substr($submenu, 0, -5);

        if ($ndepth)
          $submenu .= $this->renderMenu($child, $ndepth, $path, $myid);

        $submenu .= '</li>';
      }

      if (!empty($submenu))
        $output .= '<ul>'. $submenu .'</ul>';
    }

    return $output;
  }

  private static function countChildren(Node $node)
  {
    $count = 0;

    if (!empty($node->children))
      foreach ($node->children as $child)
        if (!empty($child->published) and empty($child->hidden))
          $count++;

    return $count;
  }
};
