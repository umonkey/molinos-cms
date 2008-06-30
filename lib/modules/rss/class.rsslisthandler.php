<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RSSListHandler extends AdminListHandler
{
  protected function setUp($preset = null)
  {
    $this->types = array('rssfeed');
    $this->title = t('Исходящие RSS ленты');
    $this->columns = array('feedicon', 'feedcheck', 'name',
      'title', 'uid', 'created');
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

  protected function getData()
  {
    $data = parent::getData();

    foreach ($data as $k => $v) {
      if (!empty($v['published']))
        $data[$k]['feedicon'] = "<a href='rss.rpc?feed={$v['name']}'><img src='lib/modules/rss/icon.png' width='28' height='28' alt='rss' /></a>";
      else
        $data[$k]['feedicon'] = '&nbsp;';
      $data[$k]['feedcheck'] = "<a href='http://feedvalidator.org/check.cgi?url=". urlencode('http://'. $_SERVER['HTTP_HOST'] .'rss.rpc?feed='. $v['name']) ."'><img src='lib/modules/rss/valid.png' alt='[Valid RSS]' title='Validate my RSS feed' width='88' height='31' /></a>";
    }

    return $data;
  }
};
