<?php

class AuthAPI
{
  public static function get_info_xml(Context $ctx)
  {
    if ($uid = $ctx->user->id)
      $xml = Node::findXML(array(
        'class' => 'user',
        'published' => 1,
        'deleted' => 0,
        'id' => $uid,
        ), $ctx->db);

    if (empty($xml))
      $xml = html::em('node', array(
        'class' => 'user',
        'name' => 'anonymous',
        ));

    return new Response($xml, 'text/xml');
  }

  public static function get_form_xml(Context $ctx)
  {
    $form = $ctx->registry->unicast('ru.molinos.cms.auth.form', array($ctx));
    return new Response($form, 'text/xml');
  }
}
