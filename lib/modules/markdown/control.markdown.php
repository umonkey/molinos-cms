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
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['rows']))
      $form['rows'] = 20;
    if (empty($form['cols']))
      $form['cols'] = 50;
    if (empty($form['description']))
      $form['description'] = t('Для оформления текста можно использовать <a href="@url" target="_blank">синтаксис markdown</a>.', array(
        '@url' => 'http://daringfireball.net/projects/markdown/syntax',
        ));

    parent::__construct($form, array('value'));
  }

  public function format($value, $em)
  {
    include_once os::path(dirname(__FILE__), 'markdown.php');
    $output = Markdown($value);

    $ctx = Context::last();
    $ctx->registry->broadcast('ru.molinos.cms.format.text', array($ctx, $this->value, &$output));

    return html::wrap($em, html::cdata(trim($output)));
  }

  public function isVisible()
  {
    return true;
  }
}
