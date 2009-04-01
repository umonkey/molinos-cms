<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSListHandler extends AdminListHandler implements iAdminList
{
  public function __construct(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'rss', 'template.xsl');
    parent::__construct($ctx);
  }

  protected function setUp($preset = null)
  {
    $this->types = array('rssfeed');
    $this->title = t('Исходящие RSS ленты');
    $this->columns = array('name', 'title', 'uid', 'created');
    $this->actions = array('clone', 'publish', 'unpublish', 'delete');

    $this->columntitles = array(
      'feedicon' => '&nbsp;',
      'feedcheck' => '&nbsp;',
      'name' => t('Имя ленты'),
      'title' => t('Заголовок'),
      'uid' => t('Создатель'),
      'created' => t('Дата добавления'),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/service/rss',
        'method' => 'on_get_list',
        'title' => t('RSS ленты'),
        'description' => t('Управление экспортируемыми данными: из каких разделов, какие данные выводить, и т.д.'),
        ),
      );
  }

  public static function on_get_list(Context $ctx)
  {
    $class = __CLASS__;
    $tmp = new $class($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }
};
