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
   * Рендерит список RSS лент.
   * 
   * @param Context $ctx 
   * @return string
   * @mcms_message ru.molinos.cms.admin.list.rss
   */
  public static function on_get_list(Context $ctx)
  {
    $class = __CLASS__;
    $tmp = new $class($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }
};
