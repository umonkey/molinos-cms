<?php

class RSSRouter implements iRequestRouter
{
  protected $query;

  public function __construct($query)
  {
    $this->query = $query;
  }

  public function route(Context $ctx)
  {
    $feed = Node::find($ctx->db, array(
      'class' => 'rssfeed',
      'deleted' => 0,
      ));

    if (empty($feed))
      throw new PageNotFoundException();
    $feed = array_shift($feed);

    if (!$feed->published)
      throw new ForbiddenException();

    $query = array(
      'class' => array(),
      'deleted' => 0,
      'published' => 1,
      '#sort' => '-id',
      '#limit' => 10,
      );

    foreach ($feed->getLinked('type') as $t)
      $query['class'][] = $t->name;

    $content = '';

    if ($id = $ctx->get('id')) {
      try {
        $filter = Node::load($id, $ctx->db);
      } catch (ObjectNotFoundException $e) {
        throw new PageNotFoundException();
      }

      switch ($filter->class) {
      case 'user':
        $query['uid'] = $filter->id;
        break;
      default:
        $query['tags'] = $filter->id;
      }

      $content .= $filter->getXML('filter');
    }

    $content .= html::em('nodes', Node::findXML($ctx->db, $query));

    $output = html::em('rss', array(
      'name' => $feed->name,
      'title' => $feed->title,
      'description' => $feed->description,
      'base' => $ctx->url()->getBase($ctx),
      'language' => $feed->language,
      ), $content);

    if (isset($feed->template) and file_exists($feed->template))
      $template = $feed->template;
    else
      $template = os::path('lib', 'modules', 'rss', 'default.xsl');

    return xslt::transform($output, $template);
  }
}
