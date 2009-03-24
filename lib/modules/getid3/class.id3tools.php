<?php

class ID3Tools
{
  public static function getFlashSize($node)
  {
    if (is_numeric($node))
      $node = Node::load($node);

    require_once dirname(__FILE__) .'/getid3/getid3.php';

    $getID3 = new getID3();
    $info = $getID3->analyze($node->getRealURL());

    return array(
      $info['video']['resolution_x'],
      $info['video']['resolution_y'],
      );
  }
}
