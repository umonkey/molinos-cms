<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeAdminWidget extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => t('Управление документами'),
      'description' => t("Редактирование и создание новых документов, публикация, другие операции."),
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['id'] = null;
    $options['rev'] = $ctx->get('rev');
    $options['mode'] = null;
    $options['parent'] = $ctx->get('parent');
    $options['class'] = $ctx->get('class');
    $options['clean'] = $ctx->get('clean');

    $options['#nocache'] = true;

    if (null === ($options['mode'] = $ctx->post('mode'))) {
      $apath = $ctx->apath;

      if (!empty($apath) and is_numeric($apath[0]))
        $options['id'] = array_shift($apath);

      if (!empty($apath))
        $options['mode'] = array_shift($apath);
    }

    // Это нужно для того, чтобы на форму можно было поместить два
    // выпадающих списка с действиями.  Если при таком раскладе мы
    // выбираем в первом действие, а во втором не выбираем ничего,
    // сервер получает две одноимённые переменные, и вторая значит
    // для него больше.  Для этого мы передаём действия массивом.
    if (is_array($options['mode'])) {
      $mode = null;

      foreach ($options['mode'] as $item) {
        if (!empty($item)) {
          $mode = $item;
          break;
        }
      }

      $options['mode'] = $mode;
    }

    // Немного валидации и дополнительной параметризации.
    switch ($options['mode']) {
    case 'purge':
      if (!in_array($options['purge'] = $ctx->apath[2], array('drafts', 'archive')))
        throw new PageNotFoundException();

      break;

    case 'edit':
    case 'raise':
    case 'sink':
    case 'publish':
    case 'unpublish':
    case 'delete':
    case 'dump':
    case 'undelete':
      if (empty($options['id']) and null === $ctx->post('checked'))
        throw new PageNotFoundException();
      break;

    case 'create':
      if (!empty($options['id']))
        throw new PageNotFoundException();

      /*
      if (empty($options['class']))
        throw new PageNotFoundException();
      */

      $options['parent'] = $ctx->get('parent');

      break;

    case 'preview':
      break;

    default:
      // bebop_debug($ctx, $options);
      throw new PageNotFoundException();
    }

    if ($options['mode'] == 'edit') {
      if ($options['clean'] !== null and $options['clean'] != 'archive' and $options['clean'] != 'draft')
        throw new PageNotFoundException();
    }

    return $this->options = $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  protected function onGetRaise(array $options)
  {
    $node = Node::load($options['id']);
    $res = $node->orderUp($options['parent']);

    bebop_on_json(array('status' => intval($res)));

    bebop_redirect($_GET['destination']);
  }

  protected function onGetSink(array $options)
  {
    $node = Node::load($options['id']);
    $res = $node->orderDown($options['parent']);

    bebop_on_json(array('status' => intval($res)));

    bebop_redirect($_GET['destination']);
  }

  protected function onGetEdit(array $options)
  {
    if (empty($options['retry']) and !empty($_SESSION['draft'])) {
      bebop_session_start();
      unset($_SESSION['draft']);
      bebop_session_end();
    }

    $node = $this->nodeLoad();
    return $this->formRender('node-edit-form', $node->formGetData());
  }

  protected function onGetCreate(array $options)
  {
    if (null !== $options['class']) {
      $node = Node::create($options['class']);
      $node->parent_id = $options['parent'];
      $html = $this->formRender('node-edit-form', $node->formGetData());
    } else {
      $ids = mcms::db()->getResultsV("id", "SELECT * FROM `node` WHERE `class` = 'type' AND `id` IN (PERMCHECK:c)");
      $types = Node::find(array('class' => 'type', 'id' => $ids));

      $url = bebop_split_url();

      $html = '<dl>';

      foreach ($types as $t) {
        $url['args'][$this->getInstanceName()]['class'] = $t->name;

        $html .= '<dt>'. l($t->title, $url['args']) .'</dt>';
        if (!empty($t->description))
          $html .= '<dd>'. mcms_plain($t->description) .'</dd>';
      }

      $html .= '</dl>';

      bebop_on_json(array('html' => $html));

      $html = '<h2>'. t('Выберите тип создаваемого документа') .'</h2>'. $html;
    }

    return $html;
  }

  protected function onGetDump(array $options)
  {
    $node = Node::load($options['id']);

    $output = "<h2>Внутренности документа</h2>";
    $output .= "<pre>". htmlspecialchars(var_export($node, true), ENT_QUOTES) ."</pre>";

    return $output;
  }

  protected function onGetPurge(array $options)
  {
    $mode = $options['purge'] == 'drafts' ? '>' : '<';

    mcms::db()->exec("DELETE FROM `node__rev` WHERE `nid` = :nid AND `rid` {$mode} (SELECT `rid` FROM `node` WHERE `id` = :id)",
      array(':nid' => $options['id'], ':id' => $options['id']));
    mcms::flush();

    bebop_redirect($_GET['destination']);
  }

  // Обработка форм.
  public function onPost(array $options, array $post, array $files)
  {
    $ids = empty($post['checked']) ? array($options['id']) : $post['checked'];

    switch ($options['mode']) {
    case 'preview':
      $node = Node::load($options['id']);
      $node->formProcess($post, null, true);

      if (null === ($output = $node->render()) or $output == '') {
        $schema = TypeNode::getSchema($node->class);
        throw new UserErrorException("Нет шаблона", 404, t("Невозможно отобразить документ: отсутствует шаблон для документов типа \"%type\".", array(
          '%type' => $schema['title'],
          '%class' => $node->class,
          )));
      }

      bebop_on_json(array('preview' => $output));
      break;
    }
  }

  // РАБОТА С ФОРМАМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Forms

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'node-edit-form':
      if ('create' == $this->ctx->apath[0]) {
        $node = Node::create($class = $this->ctx->get('class'));
        $class = 'node-'. $class .'-create-form';
      } else {
        $node = $this->nodeLoad();
        $class = 'node-'. $node->class .'-edit-form';
      }

      $form = $node->formGet(false);
      $form->addClass($class);
      $form->addClass('tabbed');

      break;

    default:
      bebop_debug("Unhandled form request: {$id}");
    }

    return $form;
  }

  public function formProcess($id, array $data)
  {
    $next = null;

    switch ($id) {
    case 'node-edit-form':
      if (!empty($data['node_content_id']))
        $node = Node::load($data['node_content_id']);
      elseif (!empty($data['node_content_class'])) {
        $node = Node::create($data['node_content_class'], array(
          'parent_id' => empty($data['node_content_parent_id']) ? null : $data['node_content_parent_id'],
          ));
      } else {
        throw new PageNotFoundException();
      }

      $next = $node->formProcess($data);

      mcms::flush();
      break;

    default:
      bebop_debug("Could not process form {$id} in class ". get_class($this) .": no handler, data follows.", $data);
    }

    return $next;
  }

  private function nodeLoad()
  {
    $filter = array(
      'id' => $this->options['id'],
      );

    if (isset($this->options['rev']))
      $filter['rid'] = $this->options['rev'];

    return Node::load($filter);
  }
};
