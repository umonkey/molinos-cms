<?php

class RSSFeed
{
  private $filter;

  public function __construct(array $filter = array())
  {
    $this->filter = array_merge(array(
      'deleted' => 0,
      'published' => 1,
      '#limit' => 50,
      '#sort' => '-id',
      ), $filter);
  }

  public function render(Context $ctx, array $options = array())
  {
    $options = array_merge(array(
      'name' => 'custom',
      'title' => MCMS_HOST_NAME,
      'xsl' => os::path('lib', 'modules', 'rss', 'default.xsl'),
      'base' => $ctx->url()->getBase($ctx),
      'description' => 'News from ' . MCMS_HOST_NAME,
      'language' => 'ru',
      ), $options);

    $content = html::wrap('nodes', Node::findXML($ctx->db, $this->filter));
    $content = html::em('rss', $options, $content);

    return xslt::transform($content, $options['xsl'], 'text/xml');
  }
}
