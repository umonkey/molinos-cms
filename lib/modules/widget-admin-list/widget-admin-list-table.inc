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

    $output .= $this->getHeaderHTML();
    $output .= $this->getListHTML($data[$this->value]);

    return $this->wrapHTML(self::makeHTML('table', array('id' => $this->id, 'class' => 'highlight'), $output));
  }

  private function getHeaderHTML()
  {
    $output = self::makeHTML('th', array(), '&nbsp;');

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

      $output .= self::makeHTML('th', array(), $text);
    }

    return self::makeHTML('tr', array(), $output);
  }

  private function getListHTML(array $documents)
  {
    $output = '';

    foreach ($documents as $nid => $doc) {
      if (empty($doc['internal']))
        $check = self::makeHTML('input', array(
          'type' => 'checkbox',
          'value' => $nid,
          'name' => 'document_list_selected[]',
          ));
      else
        $check = '&nbsp;';

      $row = self::makeHTML('td', array(), $check);

      foreach ($this->columns as $col => $title) {
        $row .= self::makeHTML('td', array(), empty($doc[$col]) ? '&nbsp;' : $doc[$col]);
      }

      $class = array(empty($doc['published']) ? 'unpublished' : 'published');

      if (empty($doc['internal']))
        $class[] = 'data';

      if (null !== $this->picker)
        $class[] = 'return'. ucfirst($this->picker);

      $output .= self::makeHTML('tr', array('class' => $class), $row);
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

    $output .= self::makeHTML('div', array('class' => 'spacer_not_ie'));

    $output = self::makeHTML('div', array('class' => 'tb_2_inside'), $output);

    $tmp = $this->class;
    $tmp[] = 'tb_2';

    return self::makeHTML('div', array('class' => $tmp), $output);
  }

  private function getSelectorHTML(array $data)
  {
    $list = array();

    foreach ($data as $k => $v)
      $list[] = "<a href='javascript:bebop_select(\"{$this->table}\",\"{$k}\");'>{$v}</a>";

    return self::makeHTML('div', array('class' => 'ctrl_left'), t('Выбрать') .': '. join(', ', $list) .'.');
  }

  private function getActionHTML(array $data)
  {
    $output = "<option value=''>". t('Выберите действие') ."</option>";

    foreach ($data as $k => $v)
      $output .= "<option value='{$k}'>&nbsp;&nbsp;{$v}</option>";

    $output = self::makeHTML('select', array('name' => $this->value .'[]'), $output)
      . self::makeHTML('input', array('type' => 'submit', 'value' => t('OK')));

    return self::makeHTML('div', array('class' => 'action_select'), $output);
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
      ."<div class='ctrl_left'><input type='text' name='{$this->value}' class='search_field' value='{$q}' />";

    if (null !== $this->sections and !empty($data[$this->sections]))
      $output .= $this->getSectionsHTML($data);

    $output .= "<input type='submit' value='Найти' /></div>";

    if (isset($this->filterform))
      $output .= "<div class='ctrl_right'><a href='". mcms_plain($this->filterform) ."' class='ctrl_filter'><span class='tip'>Фильтрация</span></a></div>";

    $output .= "</div>";

    return $output;
  }

  private function getSectionsHTML(array $data)
  {
    $output = self::makeHTML('option', array(
      'value' => '0',
      ), t('Все разделы'));

    $current = empty($data[$this->sections .'_current']) ? null : intval($data[$this->sections .'_current']);

    foreach ($data[$this->sections] as $k => $v)
      $output .= self::makeHTML('option', array(
        'value' => $k,
        'selected' => ($current == $k) ? 'selected' : null,
        ), $v);

    return self::makeHTML('select', array(
      'name' => $this->sections,
      ), $output);
  }
};
