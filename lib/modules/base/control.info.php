<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class InfoControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текстовое сообщение'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public function getHTML(array $data)
  {
    $text = $this->text;

    if (isset($this->url))
      $text .= '<p>'. mcms::html('a', array(
        'href' => $this->url,
        ), t('Подробная справка')) .'</p>';

    return isset($this->text)
      ? mcms::html('div', array('class' => 'intro'), $text)
      : null;
  }
};
