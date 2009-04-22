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

  public static function on_get_list(Context $ctx)
  {
    $tmp = new SubscriptionAdminList($ctx);
    return $tmp->getHTML();
  }
}
