<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DocListControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список документов (таблица)'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'columns'));
  }

  public function getHTML(array $data)
  {
    $output = '';

    if (null !== $this->picker)
      $output .= '<script language=\'javascript\' type=\'text/javascript\'>var mcms_picker_id = \''. $this->picker .'\';</script>';

    $output .= $this->getHeaderHTML($this->sort);
    $output .= $this->getListHTML($data[$this->value]);

    return $this->wrapHTML(mcms::html('table', array('id' => $this->id, 'class' => 'highlight'), $output));
  }

  private function getHeaderHTML(array $sort = null)
  {
    $output = mcms::html('th', array(), '&nbsp;');

    foreach ($this->columns as $key => $column) {
      if (null !== $this->widget and null !== $this->sortable and in_array($key, $this->sortable)) {
        $url = bebop_split_url();

        if (null === $sort or empty($sort[$key]) or 'asc' != strtolower($sort[$key]))
          $dir = 'asc';
        else
          $dir = 'desc';

        $url['args'][$this->widget]['sortmode'] = $dir;
        $url['args'][$this->widget]['sort'] = $key;

        $text = l($column, $url['args']);

        if (null !== $sort and !empty($sort[$key])) {
          $text .= '&nbsp;';

          if (strtolower($sort[$key]) == 'desc')
            $text .= '↑';
          else
            $text .= '↓';
        }
      } else {
        $text = $column;
      }

      $output .= mcms::html('th', array(), $text);
    }

    return mcms::html('tr', array(), $output);
  }

  private function getListHTML(array $documents)
  {
    $output = '';

    foreach ($documents as $nid => $doc) {
      if (empty($doc['internal']))
        $check = mcms::html('input', array(
          'type' => 'checkbox',
          'value' => $nid,
          'name' => 'document_list_selected[]',
          ));
      else
        $check = '&nbsp;';

      $row = mcms::html('td', array(), $check);

      foreach ($this->columns as $col => $title) {
        $row .= mcms::html('td', array(), empty($doc[$col]) ? '&nbsp;' : $doc[$col]);
      }

      $class = array(empty($doc['published']) ? 'unpublished' : 'published');

      if (empty($doc['internal']))
        $class[] = 'data';

      if (null !== $this->picker)
        $class[] = 'return'. ucfirst($this->picker);

      $output .= mcms::html('tr', array('class' => $class), $row);
    }

    return $output;
  }
};

class DocMassControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Массовые действия над документами'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'class', 'table'));
  }

  public function getHTML(array $data)
  {
    $output = '';

    if (!empty($data[$this->value]['selectors']))
      $output .= $this->getSelectorHTML($data[$this->value]['selectors']);

    if (!empty($data[$this->value]['operations']))
      $output .= $this->getActionHTML($data[$this->value]['operations']);

    $output .= mcms::html('div', array('class' => 'spacer_not_ie'));

    $output = mcms::html('div', array('class' => 'tb_2_inside'), $output);

    $tmp = $this->class;
    $tmp[] = 'tb_2';

    return mcms::html('div', array('class' => $tmp), $output);
  }

  private function getSelectorHTML(array $data)
  {
    $list = array();

    foreach ($data as $k => $v)
      $list[] = "<a href='javascript:bebop_select(\"{$this->table}\",\"{$k}\");'>{$v}</a>";

    return mcms::html('div', array('class' => 'ctrl_left'), t('Выбрать') .': '. join(', ', $list) .'.');
  }

  private function getActionHTML(array $data)
  {
    $output = "<option value=''>". t('Выберите действие') ."</option>";

    foreach ($data as $k => $v)
      $output .= "<option value='{$k}'>&nbsp;&nbsp;{$v}</option>";

    $output = mcms::html('select', array('name' => $this->value .'[]'), $output)
      . mcms::html('input', array('type' => 'submit', 'value' => t('OK')));

    return mcms::html('div', array('class' => 'action_select'), $output);
  }
};

class DocSearchControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Выбор документов в таблице'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'widget'));
  }

  public function getHTML(array $data)
  {
    $q = empty($data[$this->value]) ? '' : $data[$this->value];

    $output = ""
      ."<div class='tb_1'>"
      ."<div class='ctrl_left'>";

    if (isset($this->doctype)) {
      if (is_string($this->doctype))
        $output .= $this->getNewLink($this->doctype);
      elseif (is_array($this->doctype) and count($this->doctype) == 1)
        $output .= $this->getNewLink($this->doctype[0]);
    }

    $output .= "<input type='text' name='{$this->value}' class='search_field' value='{$q}' />";

    if (null !== $this->sections and !empty($data[$this->sections]))
      $output .= $this->getSectionsHTML($data);

    $output .= "<input type='submit' value='Найти' /></div>";

    if (isset($this->filterform))
      $output .= "<div class='ctrl_right'><a href='". mcms_plain($this->filterform) ."' class='ctrl_filter'><span class='tip'>Фильтрация</span></a></div>";

    $output .= "</div>";

    return $output;
  }

  private function getNewLink($type)
  {
    return t('<a class=\'newlink\' href=\'@url\'>Добавить</a> &nbsp;|&nbsp; ', array(
      '@url' => '/admin/node/create/?BebopNode.class='. $type .'&destination='. urlencode($_SERVER['REQUEST_URI']),
      ));
  }

  private function getSectionsHTML(array $data)
  {
    $output = mcms::html('option', array(
      'value' => '0',
      ), t('Все разделы'));

    $current = empty($data[$this->sections .'_current']) ? null : intval($data[$this->sections .'_current']);

    foreach ($data[$this->sections] as $k => $v)
      $output .= mcms::html('option', array(
        'value' => $k,
        'selected' => ($current == $k) ? 'selected' : null,
        ), $v);

    return mcms::html('select', array(
      'name' => $this->sections,
      ), $output);
  }
};
