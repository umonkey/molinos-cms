<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUINodeActions extends Control
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

  public function getHTML(array $data)
  {
    if (!count($actions = $this->filterActions()))
      return null;

    $options = '';

    $strings = array(
      'delete' => t('удалить'),
      'publish' => t('опубликовать'),
      'unpublish' => t('скрыть'),
      'clone' => t('клонировать'),
      );

    $options .= mcms::html('option', array(
      'value' => '',
      ), t('Выберите действие'));

    foreach ($actions as $action) {
      $name = array_key_exists($action, $strings) ? $strings[$action] : $action;

      $options .= mcms::html('option', array(
        'value' => $action,
        ), '&nbsp;&nbsp;'. $name);
    }

    $output = mcms::html('select', array(
      'name' => 'action[]',
      ), $options);

    $output .= mcms::html('input', array(
      'type' => 'submit',
      'value' => 'OK',
      ));

    return $output;
  }

  private function filterActions()
  {
    $actions = array_flip($this->actions);

    $user = mcms::user();

    if (!$user->hasGroup('Publishers')) {
      if (array_key_exists('publish', $actions))
        unset($actions['publish']);
      if (array_key_exists('unpublish', $actions))
        unset($actions['unpublish']);
    }

    return array_flip($actions);
  }
};
