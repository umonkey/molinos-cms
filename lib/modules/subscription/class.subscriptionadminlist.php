<?php

class SubscriptionAdminList extends AdminListHandler implements iAdminList
{
  public function __construct(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'subscription', 'template.xsl');
    parent::__construct($ctx);
  }

  protected function setUp($preset = null)
  {
    $this->types = array('subscription');
    $this->title = t('Управление рассылкой');
    $this->columns = array('name', 'last', 'created');
    $this->actions = array('publish', 'unpublish', 'delete');
  }

  /**
   * Рендерит список подписавшихся пользователей.
   * 
   * @param Context $ctx 
   * @return string
   * @mcms_message ru.molinos.cms.admin.list.subscription
   */
  public static function on_get_list(Context $ctx)
  {
    $class = __CLASS__;
    $tmp = new $class($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }
}
