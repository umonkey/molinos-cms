<?php

class AuthAPI
{
  public static function get_info_xml(Context $ctx)
  {
    if ($uid = $ctx->user->id)
      $xml = Node::findXML($ctx->db, array(
        'class' => 'user',
        'published' => 1,
        'deleted' => 0,
        'id' => $uid,
        ));

    if (empty($xml))
      $xml = html::em('node', array(
        'class' => 'user',
        'name' => 'anonymous',
        ));

    return new Response('<?xml version="1.0"?>' . $xml, 'text/xml');
  }
}
