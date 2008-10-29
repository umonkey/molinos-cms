<?php

class AdminExtRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_getlinks(Context $ctx)
  {
    $hide = mcms::modconf('adminext', 'hide', array());
    $etpl = mcms::modconf('adminext', 'edit_tpl');

    switch ($pos = strpos($url = $ctx->get('url'), '?')) {
    case 0:
      $url = substr($url, 1);
      break;
    case false:
      break;
    default:
      $url = substr($url, 0, $pos);
    }

    if (preg_match('@(\d+)$@', $url, $m)) {
      $node = Node::load($m[1]);
    } else {
      return null;
    }

    $output = '';

    foreach ($node->getActionLinks() as $action => $info) {
      if (!in_array($action, $hide)) {
        $link = $info['href'];

        if ('edit' == $action and !empty($etpl))
          $link = str_replace('$id', $node->id, $etpl);

        $link = str_replace(
          'destination=CURRENT',
          'destination='. urlencode($ctx->get('from', 'CURRENT')),
          $link);

        $a = l($link, $info['title'], array(
          'title' => $info['title'],
          'class' => 'icon-'. $info['icon'],
          ));
        $output .= mcms::html('li', $a);
      }
    }

    if (empty($output))
      $output = mcms::html('li', t('Нет доступных действий.'));

    $output = mcms::html('ul', array(
      'class' => 'nodelinks',
      ), $output);

    return mcms::html('div', array(
      'class' => 'mcms-node-actions-list-wrapper',
      ), $output);
  }
}
