<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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

    if (is_array($doctype = $this->doctype))
      $doctype = $doctype[0];

    $output .= $this->getNewLink($doctype);

    $output .= "<input type='text' name='{$this->value}' class='search_field' value='{$q}' />";

    if (null !== $this->sections and !empty($data[$this->sections]))
      $output .= $this->getSectionsHTML($data);

    $output .= "<input type='submit' value='Найти' /></div>";

    if (isset($this->filterform))
      $output .= "<div class='ctrl_right'><a href='". mcms_plain($this->filterform) ."' class='ctrl_filter'><span class='tip'>Фильтрация</span></a></div>";

    $output .= "</div>";

    return $output;
  }

  private function getNewLink($type = null)
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
