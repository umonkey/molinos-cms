<?php

class MarkdownControl extends TextAreaControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Текст (markdown)'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['rows']))
      $form['rows'] = 20;
    if (empty($form['cols']))
      $form['cols'] = 50;

    parent::__construct($form, array('value'));
  }

  public function format($value)
  {
    include_once os::path(dirname(__FILE__), 'markdown.php');
    return Markdown($value);
  }
}
