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

  public function formGet($simple = false)
  {
    $form = parent::formGet($simple);
    $form->title = $this->id ? t('Настройка RSS ленты') : t('Добавление новой RSS ленты');
    return $form;
  }

  public function getDefaultSchema()
  {
    return array(
      'name' => 'rssfeed',
      'title' => t('Исходящая RSS лента'),
      'description' => t('Описание RSS ленты, формируемой сайтом.'),
      'adminmodule' => 'rss',
      'lang' => 'ru',
      'fields' => array(
        'name' => array(
          'label' => 'Имя ленты',
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'title' => array(
          'label' => 'Видимый заголовок',
          'type' => 'TextLineControl',
          'required' => true,
          ),
        'description' => array(
          'label' => t('Описание'),
          'type' => 'TextAreaControl',
          'required' => true,
          ),
        'limit' => array(
          'label' => t('Количество элементов'),
          'type' => 'NumberControl',
          'required' => true,
          'default' => 10,
          ),
        'link' => array(
          'label' => t('Ссылка на HTML версию'),
          'type' => 'URLControl',
          'required' => true,
          'default' => 'http://HOSTNAME/',
          'description' => t('Ссылка на раздел сайта, в котором пользователи могут увидеть этот материал.  Можно использовать ключевое слово HOSTNAME: оно будет заменено на адрес сервера.'),
          ),
        'language' => array(
          'label' => t('Язык'),
          'type' => 'EnumControl',
          'required' => true,
          'default' => 'ru',
          'values' => "ru = Русский\nen = Английский",
          ),
        'sort' => array(
          'label' => t('Сортировка записей'),
          'type' => 'TextLineControl',
          'required' => true,
          'default' => '-id',
          ),
        'types' => array(
          'label' => t('Типы документов'),
          'type' => 'TextLineControl',
          'required' => true,
          'description' => t('Список внутренних имён типов документов, разделённый пробелами, например: "news article story".'),
          ),
        'contentfields' => array(
          'label' => t('Поля с содержимым'),
          'type' => 'TextLineControl',
          'required' => false,
          'description' => t('Имя одного или нескольких полей (через запятую), содержимое которых следует использовать в качестве текста записи.  Несколько значений обычно следует вводить только в том случае, если лента содержит документы разных типов, и поля с текстовым содержимым называются по-разному.  Если это поле не заполнять, записи будут отдаваться без текста (только заголовки).'),
          'default' => 'text, body, teaser',
          ),
        ),
      );
  }

  public function getRSS()
  {
    $output = '<?xml version="1.0" encoding="utf-8"?>';
    $output .= '<?xml-stylesheet href="http://'. $_SERVER['HTTP_HOST'] .'/lib/modules/rss/style.css" type="text/css" media="screen"?>';
    $output .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">';
    $output .= '<channel>';
    $output .= '<title>'. mcms_plain($this->title) .'</title>';
    $output .= '<description>'. mcms_plain($this->description) .'</description>';

    if (isset($this->link)) {
      $output .= '<link>'. str_replace('HOSTNAME', $_SERVER['HTTP_HOST'], $this->link) .'</link>';
      $output .= '<atom:link href=\'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] .'\' rel=\'self\' type=\'application/rss+xml\' />';
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
      $output .= mcms::html('pubDate', date('r', strtotime($nodes[0]->created)));

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
      );

    foreach (explode(' ', $this->sort) as $field) {
      if (substr($field, 0, 1) == '-') {
        $mode = 'desc';
        $field = substr($field, 1);
      } else {
        $mode = 'asc';
      }

      $filter['#sort'][$field] = $mode;
    }

    return Node::find($filter, $this->limit);
  }

  protected function formatItem(Node $node)
  {
    $output = '';

    if (!empty($node->name))
      $output .= mcms::html('title', mcms_plain($node->name));

    if (!empty($node->uid))
      try {
        $output .= mcms::html('dc:creator', mcms_plain(Node::load(array('class' => 'user', 'id' => $node->uid))->name));
      } catch (ObjectNotFoundException $e) {
      }

    $output .= '<guid isPermaLink="false">'. $_SERVER['HTTP_HOST'] .'/'. $node->id .'/</guid>';
    $output .= '<pubDate>'. date('r', strtotime($node->created)) .'</pubDate>';

    if (mcms::config('cleanurls'))
      $link = '/node/'. $node->id;
    else
      $link = '/?q=node%2F'. $node->id;

    $output .= mcms::html('link', 'http://'. $_SERVER['HTTP_HOST']
      .mcms::path() . $link);

    foreach (preg_split('/, */', $this->contentfields) as $field) {
      if (isset($node->$field)) {
        $output .= '<description><![CDATA['. preg_replace("/[\r\n]/", '', $node->$field) .']]></description>';
        break;
      }
    }

    return mcms::html('item', $output);
  }
};
