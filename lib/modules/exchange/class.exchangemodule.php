<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ExchangeModule
{
  public static function export($arg = array())
  {
    $list = Node::find($arg);

    $str = "<?xml version=\"1.0\" standalone=\"yes\"?>";
    $str .= "<root><nodes>";

    foreach ($list as $tmp) {
      $arr = $tmp->getRaw();
      $arrarr = array();
      $srlz = "";

      foreach ($arr as $key => $val) {
        if ($key == 'code' and is_numeric($val))
          continue;

        if (is_array($val)) {
          $arrarr[$key] = $val;
          $srlz .= mcms::html($key, array(), "<![CDATA[". serialize($val) ."]]>");
          unset($arr[$key]);
        }
      }

      $str .= mcms::html('node', $arr, $srlz);
    }

    $str .= "</nodes>";
    $str .= "<links>";

    $arr = mcms::db()->getResults("SELECT `tid`, `nid`, `key`, `order` FROM `node__rel` ORDER BY `tid`, `order`");

    foreach ($arr as $el)
      $str .= mcms::html('link',$el);

    $str .= "</links>";
    $str .= "<accessrights>";

    $arr = mcms::db()->getResults("SELECT `nid`, `uid`, `c`, `r`, `u`, `d` FROM `node__access` ORDER BY `nid`");

    foreach ($arr as $el)
      $str .= mcms::html('access', $el);

    $str .= "</accessrights>";
    $str .= "</root>";

    return $str;
  }

  public static function import($source, $isfile = false)
  {
    if ($isfile) {
      $arr = file($source);

      if (!$arr)
        return 0;

      $xmlstr = implode('', $arr);
    } else {
       $xmlstr = $source;
    }

    mcms::db()->beginTransaction();

    $xml = new SimpleXMLElement($xmlstr);

    $Nodes = array();
    $curnode = array();
    $larr = array();
    $newid = array();

    foreach ($xml->nodes->node as $node) {
      $curnode = array();

      foreach ($node->attributes() as $a => $v)
        $curnode[$a] = strval($v);

      foreach($node as $attr => $val) {
          $obj = unserialize($val);
        $curnode[$attr] = $obj;
      }

      $oldid = $curnode['id'];

      foreach (array('id', 'rid', 'left', 'right') as $k)
        if (array_key_exists($k, $curnode))
          unset($curnode[$k]);

      $SiteNode = Node::create(strval($node['class']), $curnode);
      $SiteNode->save();

      $curid = $SiteNode->id;
      $newid[$oldid] = $curid;
    }

    $at = array();

    foreach ($xml->links->link as $link) {
      foreach ($link->attributes() as $a => $v)
        $at[$a] = strval($v);

      $n = $at['nid'];
      $t = $at['tid'];

      if (array_key_exists($n,$newid))
        $nid = $newid[$n];
      if (array_key_exists($t,$newid))
        $tid = $newid[$t];

      if (!empty($nid) and !empty($tid)) {
        $key = null;
        $order = null;

        if (array_key_exists('order', $v))
          $order = $attr['order'];

        if (array_key_exists('key', $v))
          $key = $attr['key'];

        mcms::db()->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order)", array(
          ':tid' => $tid,
          ':nid' => $nid,
          ':key' => $key,
          ':order' => $order
          ));
      }
    }

    $at = array();

    foreach ($xml->accessrights->access as $acc) {
      foreach ($acc->attributes() as $a => $v)
        $at[$a] = strval($v);

      $nd = $at['nid'];
      $ud = $at['uid'];

      if (array_key_exists($nd,$newid))
        $nid = $newid[$nd];

      if (array_key_exists($ud,$newid))
        $uid = $newid[$ud];

      if (!empty($nid) and !empty($uid)) {

        $c = $r = $u = $d = 0;

        if (array_key_exists('c',$at))
          $c = $at['c'];
        if (array_key_exists('r',$at))
          $r = $at['r'];
        if (array_key_exists('u',$at))
          $u = $at['u'];
        if (array_key_exists('d',$at))
          $d = $at['d'];

        mcms::db()->exec("INSERT INTO `node__access`(`nid`, `uid`, `c`, `r`, `u`, `d`) VALUES (:nid, :uid, :c, :r, :u, :d)", array(
          ':nid' => $nid,
          ':uid' => $uid,
          ':c' => $c,
          ':r' => $r,
          ':u' => $u,
          ':d' => $d
          ));
      }
    }

    return 1;
  }

  public static function getProfileList()
  {
    $str = dirname(__FILE__).'/profiles/'.'*.xml';

    $files = glob($str);

    $plist = array();
    $pr = array();

    foreach ($files as $fn) {
      $pr['filename']  = basename($fn);

      $arr = file($fn);
      if (!$arr)
         continue;

      $xmlstr = implode('', $arr);

      $xml = new SimpleXMLElement($xmlstr);
      $at = array();
      $info = $xml->info[0];

      foreach ($info->attributes() as $a => $v)
          $at[$a] = strval($v);

      $pr['name'] = $at['name'];
      $pr['description'] = strval($info->description);
      array_push($plist, $pr);
    }

    return $plist;
  }
}
