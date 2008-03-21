<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSModule implements iRemoteCall, iAdminMenu, iAdminUI
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (null === ($name = $ctx->get('feed')))
      throw new PageNotFoundException(null, null, t('Вы не указали имя RSS потока: /rss.rpc?feed=имя.'));

    $node = Node::load(array('class' => 'rssfeed', 'name' => $name));

    bebop_debug($node);
  }

  public static function getMenuIcons()
  {
    $icons = array();

    if (mcms::user()->hasGroup('Comment Managers'))
      $icons[] = array(
        'group' => 'content',
        'href' => '/admin/?module=rss',
        'title' => t('RSS потоки'),
        'description' => t('Управление экспортируемыми данными.'),
        );

    return $icons;
  }

  public static function onGet(RequestContext $ctx)
  {
    $type = TypeNode::getSchema('rssfeed');

    if (empty($type['fields'])) {
      $type = Node::create('type', array(
        'name' => 'rssfeed',
        'title' => t('Исходящий RSS поток'),
        'lang' => 'ru',
        'fields' => array(
          'name' => array(
            'label' => 'Имя потока',
            'type' => 'TextLineControl',
            'required' => true,
            ),
          'label' => array(
            'label' => 'Видимый заголовок',
            'type' => 'TextLineControl',
            'required' => true,
            ),
          ),
        ));

      $type->save();
    }

    $tmp = new RSSListHandler($ctx);
    return $tmp->getHTML();
  }
};
