<?php

class NodeTests extends PHPUnit_Framework_TestCase
{
  public function testNewNode()
  {
    return new Node(NodeStub::create(null, get_test_context()->db));
  }

  public function testCreateNewNode()
  {
    $node = Node::create('xyz', array(
      'name' => 'hello',
      ));

    $this->assertEquals('hello', $node->name);
    $this->assertTrue($node instanceof Node);
  }

  public function testSetNodeProperty()
  {
    $node = $this->testNewNode();
    $text = 'hello, world.';
    $node->name = $text;
    $this->assertEquals($text, $node->name);
  }

  public function testIsset()
  {
    $node = $this->testNewNode();
    $this->assertFalse(isset($node->name));
    $node->name = 'hello';
    $this->assertTrue(isset($node->name));
  }

  public function testLoadNode()
  {
    $id = get_test_context()->db->getResult("SELECT `id` FROM `node` WHERE `deleted` = 0 AND `class` = 'type' AND `name` = 'domain'");
    $this->assertNotEquals(null, $id);

    $node = Node::load($id);
    $this->assertTrue($node instanceof Node);
    $this->assertEquals($id, $node->id);
    $this->assertEquals('type', $node->class);
    $this->assertEquals('domain', $node->name);
  }

  public function testFindNodes()
  {
    $nodes = Node::find(get_test_context()->db, array(
      'class' => 'type',
      ), 2);

    $this->assertEquals(true, is_array($nodes));
    $this->assertEquals(2, count($nodes));

    foreach ($nodes as $node) {
      $this->assertTrue($node instanceof NodeStub);
      $this->assertTrue(is_integer($node->id));
      $this->assertEquals('type', $node->class);
    }
  }

  public function testCountNodes()
  {
    $db = get_test_context()->db;
    $count = $db->getResult("SELECT COUNT(*) FROM `node` WHERE `class` = 'type' AND `deleted` = 0");
    $this->assertEquals($count, Node::count(array(
      'class' => 'type',
      )));
  }

  public function testCountDeletedNodes()
  {
    $db = get_test_context()->db;
    $count = $db->getResult("SELECT COUNT(*) FROM `node` WHERE `class` = 'type' AND `deleted` = 1");
    $this->assertEquals($count, Node::count(array(
      'class' => 'type',
      'deleted' => 1,
      )));
  }

  /**
   * Проверка доступа.
   *
   * Доступ всегда должен быть, т.к. мы работаем из консоли, что
   * считается административной задачей.
   */
  public function testCheckPermissions()
  {
    $node = Node::create('dummy');
    $this->assertTrue($node->checkPermission('c'));
    $this->assertTrue($node->checkPermission('d'));
  }

  /**
   * Проверка создания формы.
   *
   * Проверяется стандартный action и наличие базовых контролов.
   */
  public function testFormGet()
  {
    $node = Node::create('dummy');
    $form = $node->formGet();

    $this->assertTrue($form instanceof Form);
    $this->assertEquals('?q=nodeapi.rpc&action=create&type=dummy&destination=', $form->action);
  }

  public function testGetFormAction()
  {
    $node = Node::create('dummy');
    $this->assertEquals('?q=nodeapi.rpc&action=create&type=dummy&destination=', $node->getFormAction());
  }

  public function testFormProcess()
  {
    $node = Node::create('dummy');
    $node->formProcess(array(
      'name' => 'xyz',
      ));
    // Ничего не должно быть, т.к. для несуществующего типа dummy поле name не
    // описано.
    $this->assertFalse(isset($node->name));
  }

  /**
   * Проверка вызова несуществующего метода.
   * @expectedException RuntimeException
   */
  public function testBadMethodCall()
  {
    $node = Node::create('dummy');
    $node->aMethodThatDoesNotExist();
  }

  /**
   * Проверка получения базовой схемы.
   */
  public function testGetDefaultSchema()
  {
    $schema = Node::getDefaultSchema();
    $this->assertTrue(is_array($schema));
    $this->assertTrue(array_key_exists('name', $schema));
  }

  /**
   * Проверка получения схемы.
   * Должно быть пусто, т.к. тип не существует.
   */
  public function testGetSchema()
  {
    $node = Node::create('dummy');
    $schema = $node->getSchema();
    $this->assertTrue($schema instanceof Schema);
    $this->assertEquals(0, count($schema));
  }

  public function testGetActionLinks()
  {
    $links = Node::create('dummy')->getActionLinks();

    $this->assertTrue(is_array($links));
    foreach (array('edit', 'clone', 'delete', 'publish') as $action)
      $this->assertTrue(array_key_exists($action, $links));
  }

  public function testGetActionLinksXML()
  {
    $xml = Node::create('dummy')->getActionLinksXML();
    $this->assertTrue(0 === strpos($xml, '<links>'));
  }

  public function testGetSortedLis()
  {
    $list = Node::getSortedList('type');
    $this->assertTrue(is_array($list));
    $this->assertFalse(empty($list));

    $list = Node::getSortedList('dummy');
    $this->assertTrue(is_array($list));
    $this->assertTrue(empty($list));
  }

  public function testGetName()
  {
    $node = Node::create('dummy');
    $this->assertTrue(null === $node->getName());

    $node = Node::create('dummy', array(
      'name' => 'xyz',
      ));
    $this->assertTrue('xyz' === $node->name);
  }

  public function testGetEnabledSections()
  {
    $node = Node::create('dummy');
    $list = $node->getEnabledSections();

    $this->assertTrue(is_array($list));
    $this->assertTrue(empty($list));

    $node = Node::create('article');
    $list = $node->getEnabledSections();

    $this->assertTrue(is_array($list));
    $this->assertTrue(empty($list));
  }

  /**
   * @expectedException RuntimeException
   */
  public function testGetImage()
  {
    $node = Node::create('dummy');
    $node->getImage();
  }

  public function testGetNodesXML()
  {
    $xml = Node::getNodesXML('node', array());
    $this->assertTrue(null === $xml);

    $node = NodeStub::loadByName(get_test_context()->db, 'Molinos.CMS', 'tag');
    $xml = Node::getNodesXML('node', array($node));
    $this->assertTrue(0 === strpos($xml, '<nodes>'));
  }

  public function testListChildren()
  {
    $ctx = get_test_context();

    $root = NodeStub::loadByName($ctx->db, 'Molinos.CMS', 'tag');
    $this->assertTrue(null !== $root->id);

    $list = Node::listChildren('tag', null);
    $this->assertEquals(1, count($list));

    $list = Node::listChildren('tag', $root->id);
    $this->assertFalse(empty($list));
  }
}
