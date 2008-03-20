<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIList extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список объектов'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('columns'));
  }

  public function getHTML(array $data)
  {
    $output = '<table class=\'nodelist\' border=\'1\'>';
    $output .= $this->getTableHeader();

    $odd = true;

    foreach ($data['nodes'] as $node) {
      $classes = array();

      $classes[] = $odd ? 'odd' : 'even';
      $classes[] = empty($node['published']) ? 'unpublished' : 'published';

      if ($odd)
        $output .= '<tr class=\'odd\'>';
      else
        $output .= '<tr class=\'even\'>';

      $row = '<td class=\'selector\'>';
      if (empty($node['id']))
        $row .= '&nbsp;';
      else
        $row .= mcms::html('input', array(
          'type' => 'checkbox',
          'name' => 'nodes[]',
          'value' => $node['id'],
          ));
      $row .= '</td>';

      foreach ($this->columns as $field) {
        $row .= "<td class='field-{$field}'>";
        $value = array_key_exists($field, $node) ? $node[$field] : null;

        if (null !== ($tmp = $this->resolveField($field, $value, $node)))
          $row .= $tmp;
        elseif (empty($value))
          $row .= '&nbsp;';
        elseif ('name' == $field)
          $row .= mcms::html('a', array(
            'href' => '/admin/?mode=edit&id='. $node['id'] .'&destination='. urlencode($_SERVER['REQUEST_URI']),
            ), $value);
        else
          $row .= $value;

        $row .= '</td>';
      }

      $output .= mcms::html('tr', array(
        'class' => $classes,
        ), $row);

      $odd = !$odd;
    }

    $output .= '</table>';

    return $output;
  }

  private function getTableHeader()
  {
    $map = array(
      'name' => t('Название'),
      'title' => t('Заголовок'),
      'login' => t('Внутреннее имя'),
      'email' => t('E-mail'),
      'created' => t('Дата создания'),
      'updated' => t('Дата изменения'),
      'filename' => t('Имя файла'),
      'filetype' => t('Тип содержимого'),
      'filesize' => t('Размер'),
      'class' => t('Тип'),
      'uid' => t('Автор'),
      'thumbnail' => t('Образец'),
      'classname' => t('Класс'),
      'description' => t('Описание'),
      );

    $output = '<tr>';
    $output .= '<th class=\'selector\'>&nbsp;</th>';

    foreach ($this->columns as $col) {
      $output .= "<th class='field-{$col}'>";

      if (array_key_exists($col, $map))
        $output .= $map[$col];
      else
        $output .= $col;

      $output .= '</th>';
    }

    return $output .'</tr>';
  }

  private function resolveField($field, $value, array $node = null)
  {
    switch ($field) {
    case 'class':
      return $value;
    case 'uid':
      return $this->getUserLink($value);
    case 'thumbnail':
      if (null !== $node and !empty($node['class']) and $node['class'] == 'file') {
        if (file_exists($path = mcms::config('filestorage') .'/'. $node['filepath'])) {
          $tmp = "<a href='/attachment/{$node['id']}' title='Скачать'>";
          if (substr($node['filetype'], 0, 6) == 'image/')
            $tmp .= "<img src='/attachment/{$node['id']},48,48' alt='{$node['filepath']}' />";
          else
            $tmp .= '<img src=\'/themes/admin/img/media-floppy.png\' alt=\'download\' width=\'16\' height=\'16\' />';
          $tmp .= "</a>";
          return $tmp;
        }
      }
      break;
    case 'text':
      if (null !== $node and $node['class'] == 'comment') {
        if (!empty($node['text']))
          $text = $node['text'];
        elseif (!empty($node['body']))
          $text = $node['body'];
        else
          $text = 'n/a';
        return $text;
      }
      break;
    }
  }

  private function getUserLink($uid)
  {
    static $users = array();

    if (!array_key_exists($uid, $users))
      $users[$uid] = Node::load(array('class' => 'user', 'id' => $uid));

    if (mcms::user()->hasGroup('User Managers'))
      return mcms::html('a', array(
        'href' => "/admin/?mode=edit&id={$uid}&destination=". urlencode($_SERVER['REQUEST_URI']),
        ), $users[$uid]->name);

    return $users[$uid]->name;
  }
};
