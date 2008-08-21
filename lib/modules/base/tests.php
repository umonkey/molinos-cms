<?php

class BaseModuleTests extends PHPUnit_Framework_TestCase
{
  public function testInit()
  {
    copy(MCMS_ROOT.'/conf/default.db', MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default.ini', MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite:conf/test.db','db');
    PDO_Singleton::getInstance('default', true);
  }

  public function testRoot()
  {
    $this->assertEquals(dirname(dirname(dirname(dirname(__FILE__)))), MCMS_ROOT);
  }

  public function testCwd()
  {
    $this->assertEquals(getcwd(), MCMS_ROOT);
  }

  public function testConfig()
  {
    if ('conf/test.db' != mcms::db()->getDbName())
      throw new Exception('Bad database.');
  }

  public function testParseHTML()
  {
    $html = '<script type="text/javascript" language="javascript" '
      .'src="themes/test.js">hello</script>';

    $good = array (
      'type' => 'text/javascript',
      'language' => 'javascript',
      'src' => 'themes/test.js',
      );

    $this->assertEquals($good, mcms::parse_html($html));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testMissingArgsHTML()
  {
    $tmp = mcms::html();
    $this->assertEquals($tmp, '123');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testEmptyHTML()
  {
    $tmp = mcms::html(null);
  }

  public function testSpanHTML()
  {
    $tmp = mcms::html('span');
    $this->assertEquals('<span></span>', $tmp);
  }

  public function testSaveFindNode()
  {
    $post = array(
      'parent_id' => null,
      'name' => 'vasyapupkin@mail.ru',
       );

    $node = Node::create('user', $post);
    $node->save();

    $node = Node::find(array('name'=>'vasyapupkin@mail.ru'));
    $node = array_pop($node);
    $this->assertTrue(!empty($node));
    $nodedata = $node->getRaw();

    foreach($post as $k=>$v)
      $ndata[$k] = $nodedata[$k];

    $this->assertEquals($post, $ndata);

    $node->delete();
  }

  public function testFindNodeFail()
  {
    $node = Node::find(array('name'=>'vasyapupkin1@mail.ru'));
    $this->assertTrue(empty($node));
  }

  public function testCreateType()
  {
    $post=array (
      'node_content_name' => 'testtype',
      'node_content_title' => 'testtype',
      'node_content_description' => '123',
      'reset_rel' => '1',
      'node_content_fields' =>
      array (
        'field1' =>
        array (
          'name' => 'name',
          'label' => 'Заголовок',
          'type' => 'TextLineControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
          'required' => '1',
          'indexed' => '1',
         ),
         'field2' =>
        array (
          'name' => 'teaser',
          'label' => 'Вступление',
          'type' => 'TextAreaControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
        ),
        'field3' =>
        array (
          'name' => 'text',
          'label' => 'Текст',
          'type' => 'TextHTMLControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
          'required' => '1',
        ),
        'field4' =>
        array (
          'name' => 'created',
          'label' => 'Дата добавления',
          'type' => 'DateTimeControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
          'indexed' => '1',
        ),
        'field5' =>
        array (
          'name' => 'n1',
          'label' => 'n1',
          'type' => 'TextLineControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
        ),
        '1field5' =>
        array (
          'name' => 'n2',
          'label' => 'n2',
          'type' => 'TextLineControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
          'indexed' => '1',
        ),
        '11field5' =>
        array (
          'name' => '',
          'label' => 'Новое поле',
          'type' => 'TextLineControl',
          'dictionary' => 'widget',
          'default' => '',
          'values' => '',
        ),
      ),
      'reset_access' => '1',
      'node_content_id' => '',
      'node_content_class' => 'type',
      'node_content_parent_id' => '',
    );

    $node = Node::create('type', null);
    $node->formProcess($post);

    $node = Node::load($node->id); //проверка создания типа
    $this->assertTrue(!empty($node));

    $src_columns = array (
      'id' =>
      array (
         'type' => 'int(10)',
         'required' => true,
         'key' => 'pri',
         'default' => NULL,
         'autoincrement' => true,
      ),
      'n2' =>
      array (
         'type' => 'VARCHAR(255)',
         'required' => false,
         'key' => 'mul',
         'default' => NULL,
         'autoincrement' => false,
      )
    );
    //проверка,создалась ли индексная таблица
    $tbl = new TableInfo('node__idx_testtype');
    $tbl_columns = $tbl->getColumns();

    $this->assertEquals($tbl_columns, $src_columns);
  }

  public function testCreateDoc()
  {
    $post = array (
      'name' => 'Doc1',
      'teaser' => 'Раз-два-три-четыре-пять',
      'text' => '<p><strong>1234</strong></p>',
      'created' => '2008-07-31',
      'n1' => 'ddd',
      'n2' => 'ggg',
      'reset_rel' => '1',
      'class' => 'testtype',
      'parent_id' => '',
    );

    $node = Node::create('testtype', $post);
    $node->save();

    $node = Node::load($node->id); //проверка создания документа
    $this->assertTrue(!empty($node));

    $nodedata = $node->getRaw();
    foreach($post as $k=>$v)
      $ndata[$k] = $nodedata[$k];

    $this->assertEquals($post, $ndata);
  }

  public function testSetAccess()
  {
    $node = Node::find(array('class'=>'group','name'=>'Администраторы'));
    $node = array_pop($node);
    $this->assertTrue(!empty($node));

    $perms = array (
      'type' =>
      array (
        0 => 'c',
        1 => 'r',
        2 => 'u',
        3 => 'd',
        4 => 'p',
      ),
      'file' =>
      array (
        0 => 'c',
        1 => 'r',
        2 => 'u',
        3 => 'd',
        4 => 'p',
        ),
      'tag' =>
      array (
        0 => 'c',
        1 => 'r',
        2 => 'u',
        3 => 'd',
        4 => 'p',
      ),
      'group' =>
      array (
        0 => 'r',
      ),
      'domain' =>
      array (
        0 => 'r',
      ),
      'article' =>
      array (
        0 => 'c',
        1 => 'r',
        2 => 'u',
        3 => 'd',
        4 => 'p',
      ),
      'testtype' =>
      array (
        0 => 'c',
        1 => 'r',
        2 => 'u',
        3 => 'd',
        4 => 'p',
      ),
    );

    //переведём массив в том формат, в котором мы получаем права из GroupNode::formGetData()
    //чтобы потом можно было сделать сравнение в assertEquals
    $bdperms = array();
    foreach($perms as $k=>$v) {
      $cp = $v;
      $nv = array();
      $nv['c'] = in_array('c', $v) ? 1 : 0;
      $nv['r'] = in_array('r', $v) ? 1 : 0;
      $nv['u'] = in_array('u', $v) ? 1 : 0;
      $nv['d'] = in_array('d', $v) ? 1 : 0;
      $nv['p'] = in_array('p', $v) ? 1 : 0;
      $bdperms[$k] = $nv;
    }

    $post = array (
      'node_content_name' => 'Администраторы',
      'node_content_description' => 'Могут работать с контентом.',
      'reset_group_perm' => '1',
      'perm' => $perms,
    );

    $post = array (
      'name' => 'Администраторы',
      'description' => 'Могут работать с контентом.',
      'reset_group_perm' => '1',
      'perm' => $perms,
    );
    $node->formProcess($post);

    //проверка правильности установки прав
    $node = Node::load(array('id' => $node->id,'#recurse' => true));
    $this->assertTrue(!empty($node));

    $formdata = $node->formGetData();
    $perm = $formdata['perm'];
    $this->assertEquals($bdperms, $perm);
  }

  public function testCreateTag()
  {
    $node = Node::find(array('name'=>'Molinos.CMS'));
    $node = array_pop($node);
    $this->assertTrue(!empty($node));

    $post = array (
      'name' => 'Test-3',
      'description' => 'qwerty',
      'code' => '/qwerty/',
      'class' => 'tag',
      'parent_id' => $node->id,
    );

    $node = Node::create('tag', $post);
    $node->save();

    $node = Node::load($node->id);
    $nodedata = $node->getRaw();
    foreach($post as $k=>$v)
      $ndata[$k] = $nodedata[$k];

    $this->assertEquals($post, $ndata);
  }

  public function testRedirect()
  {
    url::__setclean(false);
    $urls = array(
      'admin?n=1' => mcms::path() .'/'. 'admin?n=1&q=admin',
      'http://ya.ru' => 'http://ya.ru',
       mcms::path() => mcms::path().'/'
      );
    foreach ($urls as $k => $v) {
      $link = url::getRedirectURL($k);
      $this->assertEquals($v, $link);
    }

    url::__setclean(true);
    $urls = array(
      'admin' => mcms::path().'/admin',
      'http://ya.ru' => 'http://ya.ru',
      'admin?n=1' => mcms::path().'/admin?n=1'
      );

    foreach ($urls as $k => $v) {
      $link = url::getRedirectURL($k);
      $this->assertEquals($v, $link);
    }
  }

  public function testRestore()
  {
    unlink(MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default_backup.ini', MCMS_ROOT.'/conf/default.ini');
    unlink(MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite:conf/default.db','db');
    PDO_Singleton::getInstance('default', true);
  }
}
