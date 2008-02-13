<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class XspfModule implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (!count($nids = explode(',', $ctx->get('nodes'))))
      throw new InvalidArgumentException('Nodes not specified.');

    $output = '';
    $tracks = array();

    foreach ($nodes = Node::find(array('class' => 'file', 'id' => $nids)) as $node) {
      $track = mcms::html('title', array(), $node->name);
      $track .= mcms::html('location', array(), 'http://'. $_SERVER['HTTP_HOST'] .'/attachment/'. $node->id .'?'. $node->filename);
      $tracks[] = mcms::html('track', array(), $track);
    }

    if (empty($tracks))
      throw new PageNotFoundException();

    header('Content-Type: application/xspf+xml; charset=utf-8');

    // TODO: если запрошен один документ, и это — не файл, можно сразу возвращать все его файлы.

    $output .= "<?xml version='1.0' encoding='utf-8'?>";
    $output .= "<playlist version='1' xmlns='http://xspf.org/ns/0/'>";
    $output .= mcms::html('trackList', array(), join('', $tracks));
    $output .= '</playlist>';

    header('Content-Length: '. strlen($output));
    die($output);
  }
};
