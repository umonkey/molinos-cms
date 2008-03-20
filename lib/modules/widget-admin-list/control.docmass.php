<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
