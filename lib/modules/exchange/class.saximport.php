<?php

class SaxImport extends SaxParser {

  private $curid = null;
  private $curnode = null;

  public function start_element($parser, $name, $attr)
  {
    switch ($name) {
      case 'node':
        $this->curnode = array();

        foreach ($attr as $a => $v)
          $this->curnode[$a] = strval($v);

        break;

      // поля ноды обрабатываются в колбэке cdata
      case 'fields':
        $this->field = true;
        break;

      // внесём записи в `node__rel`
      case 'link':
        $at = array();
        foreach ($attr as $a => $v)
          $at[$a] = strval($v);

        $n = $at['nid'];
        $t = $at['tid'];

        $nid = $n;
        $tid = $t;

        if (!empty($nid) and !empty($tid)) {
          $key = null;
          $order = null;

          if (array_key_exists('order', $attr))
            $order = $attr['order'];

          if (array_key_exists('key', $attr))
            $key = $attr['key'];

          mcms::db()->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order)", array(
            ':tid' => $tid,
            ':nid' => $nid,
            ':key' => $key,
            ':order' => $order
            ));
        }
        break;

      case 'access':
        $at = array();

        foreach ($attr as $a => $v)
          $at[$a] = strval($v);

        $nd = $at['nid'];
        $ud = empty($at['uid']) ? 0 : $at['uid'];

        $nid = $nd;
        $uid = $ud;

        if (!empty($nid)) {
          $c = empty($at['c']) ? 0 : 1;
          $r = empty($at['r']) ? 0 : 1;
          $u = empty($at['u']) ? 0 : 1;
          $d = empty($at['d']) ? 0 : 1;
          $p = empty($at['p']) ? 0 : 1;

          mcms::db()->exec("INSERT INTO `node__access`(`nid`, `uid`, `c`, `r`, `u`, `d`, `p`) VALUES (:nid, :uid, :c, :r, :u, :d, :p)", array(
            ':nid' => $nid,
            ':uid' => empty($uid) ? 0 : $uid,
            ':c' => $c,
            ':r' => $r,
            ':u' => $u,
            ':d' => $d,
            ':p' => $p,
            ));
        }
        break;

      // логгирование
      case 'nodes':
        mcms::flog('exchange', 'importing nodes');
        break;
      case 'links':
        // внесём записи в `node__rel`
        mcms::flog('exchange', 'importing relations');
        break;
      case 'accessrights':
        // Внесём записи в `node__access`
        mcms::flog('exchange', 'importing access');
        break;
    }

    return true;
  }

  public function end_element($parser, $name)
  {
    switch ($name) {
      // здесь уже будут все данные и поля для ноды после обработки fields
      case 'node':
        if (empty($this->curnode['id']))
          throw new RuntimeException(t('Отсутствует id объекта.'));
        else
          $this->curnode['__want_id'] = $this->curnode['id'];

        foreach (array('id', 'rid', 'left', 'right', '_name') as $k)
          if (array_key_exists($k, $this->curnode))
            unset($this->curnode[$k]);

        try {
          $SiteNode = Node::create(strval($this->curnode['class']), $this->curnode);
          //var_dump($SiteNode, $this->curnode);
          $SiteNode->save();

          mcms::flog('exchange', "imported a {$node['class']} node, id={$SiteNode->id}, name={$SiteNode->name}");
        } catch (Exception $e) {
          mcms::flog('exchange', $e);
        }

        $this->curid = $SiteNode->id;
        break;

      case 'fields':
        $this->field = true;
        break;
    }

    return true;
  }

  public function cdata($parser, $cdata)
  {
    if ($this->field) {
      if (false === ($obj = unserialize(urldecode($cdata))))
        mcms::flog('exchange', 'unable to unserialize: ' . $cdata);
      else {
        $this->curnode['fields'] = $obj;
      }

      $this->field = false;
    }
  }

  public function parse($xmlStream)
  {
    xml_parse($this->parser, $xmlStream);
  }
}

