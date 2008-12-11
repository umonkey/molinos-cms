<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUINodeActionsControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список действий над объектами'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form);
  }

  public function getHTML($data)
  {
    if (!count($actions = $this->filterActions()))
      return null;

    $output = '';

    $output .= $this->getSelectors();
    $output .= $this->getActions($actions);

    $output .= html::em('div', array('class' => 'spacer_not_ie'));
    $output = html::em('div', array('class' => 'tb_2_inside'), $output);

    $this->addClass('tb_2');

    return html::em('div', array('class' => $this->class), $output);
  }

  private function getSelectors()
  {
    $map = array(
      'all' => 'все',
      'none' => 'ни одного',
      'published' => 'опубликованные',
      'unpublished' => 'скрытые',
      );

    $list = array();

    foreach ($map as $k => $v)
      $list[] = "<u class='fakelink select-{$k}'>{$v}</u>";

    $output = '';

    if (!empty($this->addlink))
      $output .= l($this->addlink, t('Добавить')) .'&nbsp; | &nbsp;';

    $output .= t('Выбрать') .': '. join(', ', $list);

    if (!empty($this->actions)) {
      $output .= ' &nbsp; и &nbsp; ';

      $list = array();

      foreach ($this->actions as $action)
        $list[] = "<u class='fakelink' onclick='javascript:bebop_selected_action(\"{$action}\");'>". $this->getActionName($action) ."</u>";

      $output .= join(', ', $list);
    }

    $output .= '.';

    return html::em('div', array('class' => 'jsonly ctrl_left doc_selector'), $output);
  }

  private function getActions(array $actions)
  {
    $options = '';

    $strings = array(
      );

    $options .= html::em('option', array(
      'value' => '',
      ), t('Выберите действие'));

    foreach ($actions as $action) {
      $name = $this->getActionName($action);

      $options .= html::em('option', array(
        'value' => $action,
        ), '&nbsp;&nbsp;'. $name);
    }

    $output = html::em('select', array(
      'name' => 'action[]',
      ), $options);

    $output .= html::em('input', array(
      'type' => 'submit',
      'value' => 'OK',
      ));

    return html::em('div', array('class' => 'action_select nojs'), $output);
  }

  private function getActionName($action)
  {
    switch ($action) {
    case 'publish':
      return t('опубликовать');
    case 'unpublish':
      return t('скрыть');
    case 'delete':
      return t('удалить');
    case 'clone':
      return t('клонировать');
    case 'undelete':
      return t('восстановить');
    case 'erase':
      return t('удалить окончательно');
    case 'reindex':
      return t('индексировать');
    default:
      return $action;
    }
  }

  private function filterActions()
  {
    $actions = array_flip($this->actions);

    $user = mcms::user();

    if (!count($user->getAccess('u'))) {
      if (array_key_exists('publish', $actions))
        unset($actions['publish']);
      if (array_key_exists('unpublish', $actions))
        unset($actions['unpublish']);
    }

    return array_flip($actions);
  }
};
