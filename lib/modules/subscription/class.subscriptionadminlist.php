<?php

class SubscriptionAdminList extends AdminListHandler implements iAdminUI
{
  protected function setUp($preset = null)
  {
    $this->types = array('subscription');
    $this->title = t('Управление рассылкой');
    $this->columns = array('id', 'class', 'name', 'created');
    $this->actions = array('publish', 'unpublish', 'delete');

    $this->columntitles = array(
      'name' => t('Имя ленты'),
      'title' => t('Заголовок'),
      'uid' => t('Создатель'),
      'created' => t('Дата добавления'),
      );
  }

  public static function onGet(Context $ctx)
  {
    $tmp = new SubscriptionAdminList($ctx);
    return $tmp->getHTML();
  }
}
