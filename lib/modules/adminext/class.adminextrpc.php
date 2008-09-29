<?php

class AdminExtRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
  }

  public static function rpc_getlinks(Context $ctx)
  {
    if (false !== ($pos = strpos($url = $ctx->get('url'), '?')))
      $url = substr($url, 0, $pos);

    if (preg_match('@(\d+)$@', $url, $m)) {
      $node = Node::load($m[1]);
    } else {
      return null;
    }

    $output = '';

    foreach ($node->getActionLinks() as $action => $info) {
      $link = str_replace(
        '&destination=CURRENT',
        '&destination='. $ctx->get('from', 'CURRENT'),
        $info['href']);

      $a = l($link, $info['title'], array(
        'title' => $info['title'],
        'class' => 'icon-'. $info['icon'],
        ));
      $output .= mcms::html('li', $a);
    }

    $output = mcms::html('ul', array(
      'class' => 'nodelinks',
      ), $output);

    return mcms::html('div', array(
      'class' => 'mcms-node-actions-list-wrapper',
      ), $output);
  }
}
