<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Form extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Форма'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form);
  }

  public function getHTML(array $data)
  {
    $output = '';

    if (isset($this->title)) {
      if (!in_array($header = $this->header, array('h2', 'h3', 'h4', 'h5')))
        $header = 'h2';
      $output = "<{$header}><span>". mcms_plain($this->title) ."</span></{$header}>";
    }

    if (null != $this->intro)
      $output .= '<div class=\'intro\'>'. $this->intro .'</div>';

    $output .= mcms::html('form', array(
      'method' => isset($this->method) ? $this->method : 'post',
      'action' => isset($this->action) ? $this->action : $_SERVER['REQUEST_URI'],
      'id' => $this->id,
      'class' => $this->class,
      'enctype' => 'multipart/form-data',
      ), parent::getChildrenHTML($data));

    return $output;
  }
};
