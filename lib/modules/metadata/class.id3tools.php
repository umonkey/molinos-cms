<?php

class ID3Tools
{
  /**
   * @mcms_message ru.molinos.cms.hook.node.before
   */
  public static function on_node_update(Context $ctx, Node &$node, $op)
  {
    if ('file' != $node->class or empty($node->filepath))
      return;

    require_once dirname(__FILE__) .'/getid3/getid3.php';

    $getID3 = new getID3();
    $info = $getID3->analyze($node->getRealURL());

    $more = array(
      'width' => null,
      'height' => null,
      'duration' => null,
      'duration_sec' => null,
      'bitrate' => null,
      'bitrate_mode' => null,
      'channels' => null,
      );

    if (!empty($info['video']['resolution_x']))
      $more['width'] = $info['video']['resolution_x'];
    if (!empty($info['video']['resolution_y']))
      $more['height'] = $info['video']['resolution_y'];
    if (!empty($info['playtime_string']))
      $more['duration'] = $info['playtime_string'];
    if (!empty($info['playtime_seconds']))
      $more['duration_sec'] = $info['playtime_seconds'];
    if (!empty($info['bitrate']))
      $more['bitrate'] = $info['bitrate'];
    if (!empty($info['audio']['bitrate_mode']))
      $more['bitrate_mode'] = $info['audio']['bitrate_mode'];
    if (!empty($info['audio']['channels']))
      $more['channels'] = $info['audio']['channels'];

    if (!empty($info['error']))
      foreach ($info['error'] as $msg)
        mcms::flog("metadata[{$node->id}]: " . $msg);

    $node->metadata = $more;
  }
}
