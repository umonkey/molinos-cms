<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ContentFilterWidget extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);

    $this->groups = array(
      'Visitors',
      );
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Название виджета',
      'description' => 'Описание виджета.',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $html = parent::formRender('content-filter', array());

    return array('html' => $html);
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($id)
  {
    switch ($id) {
    case 'content-filter':
      $types = array();

      $users = array();

      foreach (Node::find(array('#sort' => array('name' => 'asc'), 'class' => 'user', 'id' => $ids = PDO_Singleton::getInstance()->getResultsV("uid", "SELECT DISTINCT `uid` FROM `node` WHERE `uid` IS NOT NULL AND `uid` <> 0 AND `deleted` = 0"))) as $user)
        $users[$user->id] = trim($user->name);

      foreach (Node::find(array('class' => 'type', 'type.hidden' => 0, 'type.internal' => 0, '#sort' => array('type.title' => 'ASC'))) as $type)
        $types[$type->name] = $type->title;

      $form = new Form(array(
        'title' => t('Фильтрация документов'),
        ));

      $form->addControl(new SetControl(array(
        'label' => t('Типы документов'),
        'value' => 'content_filter_types',
        'options' => $types,
        )));

      $form->addControl(new EnumControl(array(
        'label' => t('Автор'),
        'value' => 'content_filter_uid',
        'default' => t('(любой)'),
        'options' => $users,
        )));

      $form->addControl(new EnumControl(array(
        'label' => t('Статус публикации'),
        'value' => 'content_filter_published',
        'options' => array(
          '' => t('любой'),
          'published' => t('опубликованные'),
          'unpublished' => t('скрытые'),
          ),
        )));

      $form->addControl(new SubmitControl(array(
        'text' => t('Применить'),
        )));

      return $form;
    }
  }

  public function formProcess($id, array $data)
  {
    switch ($id) {
    case 'content-filter':
      $url = bebop_split_url();
      $url['path'] = '/admin/content/';
      $url['args']['widget'] = null;

      $url['args']['BebopContentList']['classes'] = empty($data['content_filter_types']) ? null : $data['content_filter_types'];

      if (!empty($data['content_filter_published'])) {
        $published = $data['content_filter_published'] == 'published' ? 1 : 0;
        $url['args']['BebopContentList']['published'] = $published;
      }

      if (!empty($data['content_filter_uid']))
        $url['args']['BebopContentList']['uid'] = $data['content_filter_uid'];

      return bebop_combine_url($url, false);
    }
  }
};
