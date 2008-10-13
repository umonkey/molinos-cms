<?php

class ID3Tools
{
  public static function getFlashSize($node)
  {
    if (is_numeric($node))
      $node = Node::load($node);

    if (is_object($node))
      $node = $node->getRaw();

    require_once dirname(__FILE__) .'/getid3/getid3.php';

    $getID3 = new getID3();
    $info = $getID3->analyze(mcms::config('filestorage')
      .'/'. $node['filepath']);

    return array(
      $info['video']['resolution_x'],
      $info['video']['resolution_y'],
      );
  }
}
