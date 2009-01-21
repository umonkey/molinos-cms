<?php

class SubscriptionAdminList extends AdminListHandler implements iAdminList
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
}
