<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIListControl extends Control
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
    $output = '';

    $linkfield = $this->linkfield;

    if (null !== $this->picker) {
      $this->selectors = null;
      $output .= "<script language='javascript'>var mcms_picker_id = '{$this->picker}';</script>";
    }

    $output .= '<table class=\'mcms nodelist\' border=\'0\'>';
    $output .= $this->getTableHeader();

    $odd = true;

    foreach ($data['nodes'] as $node) {
      $classes = array();

      $classes[] = $odd ? 'odd' : 'even';
      $classes[] = empty($node['published']) ? 'unpublished' : 'published';

      if (empty($node['id']) or !empty($node['_protected']))
        $classes[] = 'disabled';

      $row = '';

      if ($this->selectors) {
        $row .= '<td class=\'selector\'>';

        if (empty($node['id']) or !empty($node['_protected']))
          $row .= '&nbsp;';
        else
          $row .= mcms::html('input', array(
            'type' => 'checkbox',
            'name' => 'nodes[]',
            'value' => $node['id'],
            ));
        $row .= '</td>';
      }

      // Редактирование.
      $row .= $this->getActions($node);

      foreach ($this->columns as $field) {
        $row .= "<td class='field-{$field}'>";
        $value = array_key_exists($field, $node) ? $node[$field] : null;

        if (null !== ($tmp = $this->resolveField($field, $value, $node)))
          $row .= $tmp;
        elseif ((null !== $linkfield) and ($linkfield == $field)) {
          if (!empty($node['#link'])) {
            $href = $node['#link'];
          } else {
            $href = isset($this->picker)
              ? "att.php?q={$node['id']}"
              : '/admin/?mode=edit&cgroup='. $_GET['cgroup'] .'&id='. $node['id'] .'&destination=CURRENT';
          }

          $row .= mcms::html('a', array(
            'href' => $href,
            'class' => isset($this->picker) ? 'returnHref' : null,
            'onclick' => isset($this->picker) ? "return mcms_picker.mySubmit(\"". l('att.php?q='. $node['id']) ."\",{$node['id']})" : null,
            ), empty($value) ? '(без названия)' : mcms_plain($value, false));
        } elseif (empty($value))
          $row .= '&nbsp;';
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
    $map = $this->getColumnTitles();

    $output = '<tr>';

    if ($this->selectors)
      $output .= '<th class=\'selector\'>&nbsp;</th>';

    // Редактирование.
    if (!$this->noedit)
      $output .= '<th class=\'actions\'>&nbsp;</th>';

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

  // Конверсия значения поля в текстовую форму.  Если метод возвращает
  // NULL (или ничего не возвращает), значение используется в чистом виде.
  private function resolveField($field, $value, array $node = null)
  {
    switch ($field) {
    case 'class':
      if (null === ($schema = TypeNode::getSchema($value)))
        return $value .'(???)';

      if (empty($schema['title']))
        return $value;

      return mb_strtolower($schema['title']);

    case 'uid':
      return $this->getUserLink($value);

    case 'thumbnail':
      if (null !== $node and !empty($node['class']) and $node['class'] == 'file') {
        if (file_exists($path = mcms::config('filestorage') .'/'. $node['filepath'])) {
          if (substr($node['filetype'], 0, 6) == 'image/')
            $tmp = mcms::html('img', array(
              'src' => "att.php?q={$node['id']},48,48,c",
              'width' => 48,
              'height' => 48,
              'alt' => $node['filepath'],
              'onclick' => isset($this->picker)
                ? "return mcms_picker.mySubmit(\"". l('att.php?q='. $node['id']) ."\",{$node['id']})"
                : null,
              ));
          else
            $tmp = mcms::html('img', array(
              'src' => 'themes/admin/img/media-floppy.png',
              'width' => 16,
              'height' => 16,
              'alt' => t('Скачать'),
              ));

          $tmp = mcms::html('a', array(
            'title' => 'Скачать',
            'href' => "att.php?q={$node['id']}",
            'class' => isset($this->picker) ? 'returnHref' : null,
            ), $tmp);

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

    case 'email':
      if (!empty($value))
        return mcms::html('a', array('href' => 'mailto:'. str_replace(' ', '', $value)), $value);
      break;

    case 'filesize':
      return number_format($value, 0);

    case 'name':
      if (isset($node['class']) and 'user' == $node['class']) {
        if (strstr($value, '@'))
          $class = 'email';
        elseif (strstr($value, '.'))
          $class = 'openid';
        else
          $class = null;

        return mcms::html('a', array(
          'href' => '/admin/?mode=edit&cgroup='. $_GET['cgroup'] .'&id='. $node['id'] .'&destination=CURRENT',
          'class' => $class,
          ), $value);
      }
      break;
    }
  }

  private function getUserLink($uid)
  {
    static $users = array();

    if (empty($uid))
      return 'anonymous';

    if (!array_key_exists($uid, $users)) {
      try {
        $users[$uid] = Node::load(array('class' => 'user', 'id' => $uid));
      } catch (ObjectNotFoundException $e) {
        return '???';
      }
    }

    if (mcms::user()->hasAccess('u', 'user'))
      return mcms::html('a', array(
        'href' => "/admin/?mode=edit&id={$uid}&destination=CURRENT",
        ), $users[$uid]->name);

    return $users[$uid]->name;
  }

  private function getColumnTitles()
  {
    if (is_array($this->columntitles))
      return $this->columntitles;

    return array(
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
      'timestamp' => t('Время'),
      'username' => t('Пользователь'),
      'operation' => t('Действие'),
      'message' => t('Сообщение'),
      'ip' => t('IP адрес'),
      );
  }

  private function getActions(array $node)
  {
    $output = array();

    if (null !== ($tmp = $this->getDebugLink($node)))
      $output[] = $tmp;
    if (null !== ($tmp = $this->getZoomLink($node)))
      $output[] = $tmp;
    if (null !== ($tmp = $this->getViewLink($node)))
      $output[] = $tmp;

    if (!empty($output)) {
      return mcms::html('td', array(
        'class' => 'actions',
        'style' => 'padding-left: 4px; padding-right: 4px; width: '. (18 * count($output)) .'px',
        ), join('', $output));
    }
    else {
      return "<td></td>";
    }
  }

  private function getDebugLink(array $node)
  {
    if (!bebop_is_debugger() or empty($node['id']))
      return;

    return $this->getIcon('lib/modules/admin/img/debug.gif', "nodeapi.rpc?action=dump&node=". $node['id'], t('Просмотреть внутренности'));
  }

  private function getViewLink(array $node)
  {
    if (empty($node['class']) or empty($node['id']) or !empty($node['deleted']) or empty($node['published']))
      return;

    return $this->getIcon('themes/admin/img/icon-www.png', '/nodeapi.rpc?action=locate&node='. $node['id'], t('Найти на сайте'));
  }

  private function getZoomLink(array $node)
  {
    if (!$this->zoomlink)
      return;

    if (empty($node['class']) or empty($node['id']) or !mcms::user()->hasAccess('u', $node['class']))
      return;

    if (!empty($node['deleted']))
      return;

    return $this->getIcon('themes/admin/img/zoom.png', str_replace(array('NODEID', 'NODENAME'), array($node['id'], $node['name']), $this->zoomlink), t('Найти'));
  }

  private function getIcon($img, $href, $title)
  {
    $tmp = mcms::html('img', array(
      'src' => $img,
      'width' => 16,
      'height' => 16,
      'alt' => $title,
      ));

    return mcms::html('a', array(
      'class' => 'icon',
      'href' => $href,
      'title' => $title,
      ), $tmp);
  }
};
