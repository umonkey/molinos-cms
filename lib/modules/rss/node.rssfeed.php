<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class RssfeedNode extends Node
{
  public function save()
  {
    parent::checkUnique('name', t('RSS лента с таким именем уже существует.'));

    return parent::save();
  }

  public function duplicate()
  {
    $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();
    parent::duplicate();
  }

  public function formGet()
  {
    $form = parent::formGet();
    $form->title = $this->id ? t('Настройка RSS ленты') : t('Добавление новой RSS ленты');
    return $form;
  }

  public function canEditFields()
  {
    return false;
  }

  public function getFormFields()
  {
    $schema = array(
      'name' => array(
        'label' => t('Имя ленты'),
        'type' => 'TextLineControl',
        'required' => true,
        'weight' => 1,
        'group' => t('Основные свойства'),
        ),
      'title' => array(
        'label' => t('Видимый заголовок'),
        'type' => 'TextLineControl',
        'required' => true,
        'weight' => 2,
        'group' => t('Основные свойства'),
        ),
      'description' => array(
        'label' => t('Описание'),
        'type' => 'TextAreaControl',
        'required' => true,
        'weight' => 3,
        'group' => t('Основные свойства'),
        ),
      'language' => array(
        'label' => t('Язык'),
        'type' => 'EnumControl',
        'required' => true,
        'default' => 'ru',
        'values' => array(
          'ru' => 'Русский',
          'en' => 'Английский',
          ),
        'group' => t('Основные свойства'),
        'weight' => 4,
        ),
      'types' => array(
        'label' => t('Типы документов'),
        'type' => 'SetControl',
        'required' => true,
        'dictionary' => 'type',
        'group' => t('Формат выдачи'),
        'weight' => 10,
        ),
      'contentfields' => array(
        'label' => t('Поле с содержимым'),
        'type' => 'EnumControl',
        'required' => false,
        'dictionary' => 'field',
        'group' => t('Формат выдачи'),
        'weight' => 20,
        ),
      'limit' => array(
        'label' => t('Количество элементов'),
        'type' => 'NumberControl',
        'required' => true,
        'default' => 10,
        'group' => t('Формат выдачи'),
        'weight' => 30,
        ),
      'sort' => array(
        'label' => t('Сортировка записей'),
        'type' => 'TextLineControl',
        'required' => true,
        'default' => '-id',
        'group' => t('Формат выдачи'),
        'weight' => 40,
        ),
      );

    return new Schema($schema);
  }

  public function getRSS(Context $ctx)
  {
    if ('no' == $ctx->get('limit'))
      $this->limit = null;

    $output = '<?xml version="1.0" encoding="utf-8"?>';
    $output .= '<?xml-stylesheet href="http://'. $_SERVER['HTTP_HOST'] .'/lib/modules/rss/style.css" type="text/css" media="screen"?>';
    $output .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">';
    $output .= '<channel>';
    $output .= '<title>'. mcms_plain($this->title) .'</title>';
    $output .= '<description>'. mcms_plain($this->description) .'</description>';

    if (isset($this->link)) {
      $output .= '<link>'. str_replace('HOSTNAME', $_SERVER['HTTP_HOST'], $this->link) .'</link>';
      $output .= '<atom:link href=\'http://'. mcms_plain($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) .'\' rel=\'self\' type=\'application/rss+xml\' />';
    }

    if (isset($this->language))
      $output .= '<language>'. $this->language .'</language>';

    $output .= '<generator>http://molinos-cms.googlecode.com/</generator>';

    $output .= $this->formatItems();

    $output .= '</channel></rss>';

    return $output;
  }

  protected function formatItems()
  {
    $output = '';

    if (count($nodes = array_values($this->loadItems()))) {
      $output .= html::em('pubDate', date('r', strtotime($nodes[0]->created)));

      foreach ($nodes as $node)
        $output .= $this->formatItem($node);
    }

    return $output;
  }

  protected function loadItems()
  {
    $filter = array(
      'published' => 1,
      'class' => preg_split('/, */', $this->types),
      '#sort' => $this->sort,
      );

    return Node::find($filter, $this->limit);
  }

  protected function formatItem(Node $node)
  {
    $output = '';

    if (!empty($node->name))
      $output .= html::em('title', mcms_plain($node->name));

    if (!empty($node->uid))
      try {
        $output .= html::em('dc:creator', mcms_plain(Node::load(array('class' => 'user', 'id' => $node->uid))->name));
      } catch (ObjectNotFoundException $e) {
      }

    $output .= '<guid isPermaLink="false">'. $_SERVER['HTTP_HOST'] .'/'. $node->id .'/</guid>';
    $output .= '<pubDate>'. date('r', strtotime($node->created)) .'</pubDate>';

    if (mcms::config('cleanurls'))
      $link = '/node/'. $node->id;
    else
      $link = '/?q=node%2F'. $node->id;

    $output .= html::em('link', 'http://'. $_SERVER['HTTP_HOST']
      .mcms::path() . $link);

    foreach (preg_split('/, */', $this->contentfields) as $field) {
      if (isset($node->$field)) {
        $output .= '<description><![CDATA['. preg_replace("/[\r\n]/", '', $node->$field) .']]></description>';
        break;
      }
    }

    if (!empty($node->file))
      $output .= $this->getEnclosure($node->file);
    elseif (!empty($node->files))
      foreach ($node->files as $file)
        $output .= $this->getEnclosure($file);

    return html::em('item', $output);
  }

  private function getEnclosure($file)
  {
    if (!empty($_GET['__cleanurls']))
      $url = 'http://'. $_SERVER['HTTP_HOST'] . mcms::path()
        .'/attachment/'. $file->id .'/'. $file->filename;
    else
      $url = 'http://'. $_SERVER['HTTP_HOST'] . mcms::path()
        .'/?q=attachment.rpc&fid='. $file->id;

    return html::em('enclosure', array(
      'url' => $url,
      'length' => $file->filesize,
      'type' => $file->filetype,
      ));
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();

    $links['locate']['href'] = '?q=rss.rpc&feed='. $this->name;
    $links['locate']['icon'] = 'feed';

    $links['validate'] = array(
      'href' => 'about:blank',
      'title' => t('Валидировать'),
      'icon' => 'validate',
      );

    return $links;
  }
};
