<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIListControl extends Control
{
  private $_actions = array();

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

  private function getGroup()
  {
    if (array_key_exists('cgroup', $_GET))
      return $_GET['cgroup'];
    elseif (preg_match('@^admin/([a-z]+)/@', $_GET['q'], $m))
      return $m[1];
    return 'content';
  }

  public function getHTML($data)
  {
    $output = '';

    $linkfield = $this->linkfield;

    if (null !== $this->picker) {
      $this->selectors = null;
      $output .= "<script language='javascript'>var mcms_picker_id = '{$this->picker}';</script>";
    }
    $preset  = $data->preset;
    $output .= '<table class=\'mcms nodelist\' border=\'0\'>';
    $output .= $this->getTableHeader($data);

    $odd = true;

    foreach ($data->nodes as $node) {
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
          $row .= html::em('input', array(
            'type' => 'checkbox',
            'name' => 'nodes[]',
            'value' => $node['id'],
            ));
        $row .= '</td>';
      }

      // Редактирование.
      $row .= $this->getActions($node, $preset);

      foreach ($this->columns as $field) {
        $row .= "<td class='field-{$field}'>";
        $value = array_key_exists($field, $node) ? $node[$field] : null;

        if (null !== ($tmp = $this->resolveField($field, $value, $node)))
          $row .= $tmp;
        elseif ((null !== $linkfield) and ($linkfield == $field)) {
          if (!empty($node['#link'])) {
            $href = $node['#link'];
          } elseif (!empty($node['#nolink'])) {
            $href = null;
          } else {
            $href = isset($this->picker)
              ? "?q=attachment.rpc&fid={$node['id']}"
              : '?q=admin/'. $this->getGroup() .'/edit/'. $node['id'] .'&destination=CURRENT';
          }

          if ($href)
            $row .= html::em('a', array(
              'href' => $href,
              'class' => isset($this->picker) ? 'returnHref' : null,
              'onclick' => isset($this->picker) ? "return mcms_picker.mySubmit(\"". l('?q=attachment.rpc&fid='. $node['id']) ."\",{$node['id']})" : null,
              ), empty($value) ? '(без названия)' : mcms_plain($value, false));
          else
            $row .= mcms_plain($value, false);
        } elseif (empty($value))
          $row .= '&nbsp;';
        else
          $row .= $value;

        $row .= '</td>';
      }

      $output .= html::em('tr', array(
        'class' => $classes,
        ), $row);

      $odd = !$odd;
    }

    $output .= '</table>';

    return $output;
  }

  private function getTableHeader($data)
  {
    $map = $this->getColumnTitles();

    $output = '<tr>';

    if ($this->selectors)
      $output .= '<th class=\'selector\'>&nbsp;</th>';

    $output .= $this->getActionsHeader($data);

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
      $type = Node::load(array(
        'class' => 'type',
        'deleted' => 0,
        'name' => $value,
        ));

      if (empty($type->title))
        return $value;

      return mb_strtolower($type->title);

    case 'created':
    case 'updated':
      $orig = $value;

      // Прибавляем смещение, т.к. strtotime() оперирует локальными датами и
      // автоматически отнимает это смещение, а наша дата уже в GMT.
      if (!is_numeric($value))
        $value = strtotime($value) + date('Z', time());

      $result = date('d.m', $value);

      if (date('Y') != ($year = date('Y', $value)))
        $result .= '.'. $year;

      $result .= date(', H:i', $value);

      return $result;

    case 'uid':
      return $this->getUserLink($value);

    case 'thumbnail':
      if (null !== $node and !empty($node['class']) and $node['class'] == 'file') {
        if (file_exists($path = mcms::config('filestorage') .'/'. $node['filepath'])) {
          $tmp = html::em('img', array(
            'src' => "?q=attachment.rpc&fid={$node['id']},48,48,c&rev={$node['rid']}",
            'width' => 48,
            'height' => 48,
            'alt' => $node['filepath'],
            'onclick' => isset($this->picker)
              ? "return mcms_picker.mySubmit(\"". l('?q=attachment.rpc&fid='. $node['id']) ."\",{$node['id']})"
               : null,
            ));

          $tmp = html::em('a', array(
            'title' => 'Скачать',
            'href' => "?q=attachment/{$node['id']}/". urlencode($node['filename']),
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
        return html::em('a', array('href' => 'mailto:'. str_replace(' ', '', $value)), $value);
      break;

    case 'filesize':
      return number_format($value, 0);

    case 'name':
      if (isset($node['class']) and 'user' == $node['class']) {
        if (strstr($value, '@'))
          $class = 'email';
        elseif (strstr($value, '.')) {
          $class = 'openid';
          $url = new url($value);
          $value = trim($url->host . $url->path, '/');
        } else
          $class = null;

        return html::em('a', array(
          'href' => '?q=admin/'. $this->getGroup() .'/edit/'. $node['id'] .'&destination=CURRENT',
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

    if ($uid instanceof Node) {
      $users[$uid->id] = $uid;
      $uid = $uid->id;
    } elseif (is_array($uid)) {
      $users[$uid['id']] = Node::create('user', $uid);
      $uid = $uid['id'];
    } elseif (!array_key_exists($uid, $users)) {
      try {
        $users[$uid] = Node::load(array('class' => 'user', 'id' => $uid));
      } catch (ObjectNotFoundException $e) {
        return '???';
      }
    }

    $name = empty($users[$uid]->fullname)
      ? $users[$uid]->name
      : $users[$uid]->fullname;

    if (mcms::user()->hasAccess('u', 'user'))
      return html::em('a', array(
        'href' => "?q=admin/". $this->getGroup()
          ."/edit/{$uid}&destination=CURRENT",
        ), $name);

    return $name;
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

  private function getActions(array $node, $preset = null)
  {
    $output = '';

    $links = empty($node['_links']) ? array() : $node['_links'];

    foreach ($this->_actions as $key) {
      if (array_key_exists($key, $links) and is_array($links[$key])) {
        $link = html::em('a', array(
          'href' => $links[$key]['href'],
          'class' => 'icon-'. $links[$key]['icon'],
          'title' => $links[$key]['title'],
          ), html::em('span', $links[$key]['title']));
      } else {
        $link = '';
      }

      $output .= html::em('td', array(
        'class' => 'icon',
        ), $link);
    }

    return $output;
  }

  private function getIcon($img, $href, $title)
  {
    $tmp = html::em('img', array(
      'src' => $img,
      'width' => 16,
      'height' => 16,
      'alt' => $title,
      ));

    return html::em('a', array(
      'class' => 'icon',
      'href' => $href,
      'title' => $title,
      ), $tmp);
  }

  /**
   * Формирует заголовок для возможных действий.
   *
   * Список действий сохраняется в $this->_actions.
   */
  private function getActionsHeader($data)
  {
    $actions = array();
    $skip = is_array($this->actions)
      ? $this->actions
      : array();

    foreach ($data->nodes as $node) {
      if (!empty($node['_links'])) {
        foreach ($node['_links'] as $key => $val) {
          if (!in_array($key, $actions) and !in_array($key, $skip) and is_array($val))
            $actions[] = $key;
        }
      }
    }

    $this->_actions = $actions;

    return html::em('th', array(
      'colspan' => count($actions),
      ));
  }
};
