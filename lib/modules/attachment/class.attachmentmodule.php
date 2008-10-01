<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AttachmentModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    $att = new Attachment($ctx);
    $att->sendFile();
  }

  public static function rpc_find(Context $ctx)
  {
    $nodes = Node::find(array(
      'class' => 'file',
      'name' => '%'. trim($ctx->get('search')) .'%',
      ), 5);

    $output = '';

    foreach ($nodes as $node) {
      $c1 = mcms::html('input', array(
        'type' => 'radio',
        'name' => $ctx->get('name', 'unknown') .'[id]',
        'value' => $node->id,
        ));

      $c2 = mcms::html('img', array(
        'alt' => $node->filename,
        'width' => 50,
        'height' => 50,
        'src' => '?q=attachment.rpc&fid='. $node->id
          .',50,50,cw'
        ));

      $c3 = t('<a href=\'@url\'>%name</a><br />Размер: %size', array(
        '@url' => '?q=attachment.rpc&fid='. $node->id,
        '%name' => $node->name,
        '%size' => $node->filesize,
        ));

      $row = mcms::html('td', array('class' => 'check'), $c1);
      $row .= mcms::html('td', $c2);
      $row .= mcms::html('td', array('class' => 'info'), $c3);

      $output .= mcms::html('tr', $row);
    }

    die(mcms::html('table', array(
      'class' => 'options',
      ), $output));
  }
};
