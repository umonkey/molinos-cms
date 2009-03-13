<?php

class MarkdownControl extends TextAreaControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Текст (markdown)'),
      'class' => __CLASS__,
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['rows']))
      $form['rows'] = 20;
    if (empty($form['cols']))
      $form['cols'] = 50;
    if (empty($form['description']))
      $form['description'] = t('Вы можете использовать <a href="@url" target="_blank">синтаксис markdown</a> при оформлении текста.', array(
        '@url' => 'http://daringfireball.net/projects/markdown/syntax',
        ));

    parent::__construct($form, array('value'));
  }

  public function format($value)
  {
    include_once os::path(dirname(__FILE__), 'markdown.php');
    return Markdown($value);
  }
}
