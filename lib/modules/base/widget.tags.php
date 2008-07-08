<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TagsWidget extends Widget
{
  public function __construct(Node $node = null)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список разделов',
      'description' => 'Возвращает список разделов в виде дерева (переменная «$sections»).  Конкретный раздел, с которого будет начинаться дерево, может быть указан в адресной строке или жёстко задан ниже.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_fixed',
      'label' => t('Раздел по умолчанию'),
      'description' => t('Здесь можно выбрать раздел, который будет использован, если из адреса текущего запроса вытащить код раздела не удалось.'),
      'options' => TagNode::getTags('select'),
      'default' => t('не используется'),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_forcefixed',
      'label' => t('Всегда использовать этот раздел'),
      'description' => t('Всегда возвращать информацию о выбранном разделе, независимо от того, в каком разделе находится посетитель.'),
      )));

    return $form;
  }

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

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    if ($this->forcefixed or (null === ($options['root'] = $ctx->section_id)))
      $options['root'] = $this->fixed;

    $options['dynamic'] = ($ctx->section_id !== null);

    return $options;
  }
};
