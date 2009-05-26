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
    $this->sort = 'name';
  }

  public static function on_get_list(Context $ctx)
  {
    $tmp = new SubscriptionAdminList($ctx);
    return $tmp->getHTML(null, array(
      '#raw' => true,
      ));
  }
}
