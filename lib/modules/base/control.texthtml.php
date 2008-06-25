<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TextHTMLControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текст с форматированием'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $content = (isset($this->value) and !empty($data[$this->value])) ? htmlspecialchars($data[$this->value]) : null;

    if (mcms::ismodule('tinymce'))
      TinyMceModule::add_extras();

    $output = mcms::html('textarea', array(
      'id' => $this->id,
      'class' => 'form-text visualEditor',
      'name' => $this->value,
      ), $content);

    return $this->wrapHTML($output);
  }
};
