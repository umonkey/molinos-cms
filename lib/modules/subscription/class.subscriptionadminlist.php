<?php

class SubscriptionAdminList extends AdminListHandler implements iAdminUI
{
  protected function setUp($preset = null)
  {
    $this->types = array('subscription');
    $this->title = t('Управление рассылкой');
    $this->columns = array('name', 'last', 'created');
    $this->actions = array('publish', 'unpublish', 'delete');

    $this->columntitles = array(
      'name' => t('Почтовый адрес'),
      'last' => t('Последняя новость'),
      'created' => t('Дата подписки'),
      );
  }

  public static function onGet(Context $ctx)
  {
    $tmp = new SubscriptionAdminList($ctx);
    return $tmp->getHTML();
  }
}
