<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DocWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Отдельный документ',
      'description' => 'Возвращает в переменную «$document» отдельный документ, идентификатор которого может быть передан либо через адресную строку, либо задан жёстко ниже.  Если идентификатор документа не указан, не возвращает ничего; если идентификатор указан, но документа с таким идентификатором нет, или он не опубликован — возникает ошибка 404.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_mode',
      'label' => t('Режим работы'),
      'required' => true,
      'options' => array(
        'view' => t('Просмотр'),
        'edit' => t('Редактирование'),
        ),
      )));

    $form->addControl(new NumberControl(array(
      'value' => 'config_fixed',
      'label' => t('Фиксированный документ'),
      'description' => t("Документ с указанным здесь кодом будет возвращён "
        ."если из адреса запрошенной страницы достать код документа "
        ."не удалось (он не указан или так настроена страница)."
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_showneighbors',
      'label' => t('Возвращать информацию о соседях'),
      )));

    return $form;
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    if (null === ($options['action'] = $ctx->get('action', $this->mode)))
      $options['action'] = 'view';

    if ($uid = mcms::user()->id) {
      $options['cachecontrol'] = $uid;
      $options['uid'] = $uid;
    } else {
      $options['cachecontrol'] = array_keys(mcms::user()->getGroups());
    }

    if ($this->showneighbors)
      $options['section'] = $ctx->section_id;

    if (empty($this->fixed))
      $options['root'] = $ctx->document_id;
    else
      $options['root'] = $this->fixed;

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  protected function onGetView(array $options)
  {
    $result = array(
      'document' => array(),
      'tags' => array(),
      'schema' => array(),
      );

    if ($root = $options['root']) {
      $node = Node::load(array('id' => $options['root']));

      if (in_array($node->class, array('tag', 'config')))
        throw new PageNotFoundException();

      if (!$node->published)
        throw new ForbiddenException(t('Документ не опубликован.'));

      if (!$node->checkPermission('r'))
        throw new ForbiddenException(t('У вас нет доступа к этому документу.'));

      $result['document'] = $node->getRaw();

      $sections = array();

      if (count($sids = $node->linkListParents('tag', true))) {
        foreach (Node::find(array('class' => 'tag', 'id' => $sids, 'published' => 1)) as $tag) {
          $sections[] = $tag->id;
          $result['tags'][] = $tag->getRaw();
        }
      }

      $result['schema'] = TypeNode::getSchema($node->class);

      if (!empty($result['schema']['fields']) and is_array($result['schema']['fields'])) {
        foreach ($result['schema']['fields'] as $k => $v) {
          switch ($v['type']) {
          case 'NodeLinkControl':
            if (empty($result['document'][$k]))
              $result['document'][$k] = null;
            else {
              if (!empty($v['dictionary']))
                $parts = array($v['dictionary'], 'name');
              else
                $parts = explode('.', $v['values'], 2);

              if (count($tmp = array_values(Node::find(array('class' => $parts[0], 'id' => $result['document'][$k]), 1))))
                $result['document'][$k] = $tmp[0]->getRaw();
              else
                $result['document'][$k] = null;
            }
            break;
          }
        }
      }

      if ($this->showneighbors and $this->ctx->section_id !== null and in_array($this->ctx->section->id, $sections)) {
        if (null !== ($n = $node->getNeighbors($this->ctx->section->id))) {
          $result['neighbors'] = array(
            'prev' => empty($n['right']) ? null : $n['right']->getRaw(),
            'next' => empty($n['left']) ? null : $n['left']->getRaw(),
            );
        }
      }
    }

    if (array_key_exists('document', $result))
      bebop_on_json(array($result['document']));

    return $result;
  }

  protected function onGetEdit(array $options)
  {
    $node = Node::load($options['root']);

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return $form->getHTML($node->formGetData());
  }
};
