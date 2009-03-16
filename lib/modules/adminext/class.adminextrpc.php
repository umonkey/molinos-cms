<?php

class AdminExtRPC extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.adminext
   */
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function rpc_getlinks(Context $ctx)
  {
    $hide = $ctx->modconf('adminext', 'hide', array());
    $etpl = $ctx->modconf('adminext', 'edit_tpl');

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

        $a = html::link($link, $info['title'], array(
          'title' => $info['title'],
          'class' => 'icon-'. $info['icon'],
          ));
        $output .= html::em('li', $a);
      }
    }

    if (empty($output))
      $output = html::em('li', t('Нет доступных действий.'));

    $output = html::em('ul', array(
      'class' => 'nodelinks',
      ), $output);

    return new Response(html::em('div', array(
      'class' => 'mcms-node-actions-list-wrapper',
      ), $output));
  }
}
