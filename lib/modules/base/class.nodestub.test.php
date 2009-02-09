<?php

class NodeStubTests extends PHPUnit_Framework_TestCase
{
  private function getStub()
  {
    return NodeStub::create(null, get_test_context()->db);
  }

  private function getRootTag()
  {
    return NodeStub::loadByName(get_test_context()->db, 'Molinos.CMS', 'tag');
  }

  public function testCreate()
  {
    $stub = $this->getStub();
    $this->assertTrue($stub instanceof NodeStub);
    $this->assertEquals(null, $stub->id);
  }

  public function testGetSet()
  {
    $stub = $this->getStub();
    $this->assertEquals(null, $stub->xyz);
    $this->assertFalse(isset($stub->xyz));

    $stub->xyz = 'xyz';
    $this->assertEquals(true, isset($stub->xyz));
    $this->assertEquals('xyz', $stub->xyz);
  }

  /**
   * @expectedException RuntimeException
   */
  public function testBadMethodCall()
  {
    $stub = $this->getStub();
    $stub->badMethodCall();
  }

  public function testGetXML()
  {
    $stub = $this->getStub();
    $this->assertEquals('<node />', $stub->getXML());
    $this->assertEquals('<test>ok</test>', $stub->getXML('test', 'ok'));
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testChangeId()
  {
    $stub = $this->getStub();
    $stub->id = 123;
    $stub->id = 234;
  }

  /**
   * @expectedException RuntimeException
   */
  public function testSaveWithoutTransaction()
  {
    $stub = $this->getStub();

    $stub->class = 'dummy';
    $stub->name = 'test';
    $stub->save();
  }

  /**
   * @expectedException PDOException
   */
  public function testWithoutClass()
  {
    $db = get_test_context()->db;

    $stub = $this->getStub();
    $stub->name = 'test';

    $db->beginTransaction();
    $stub->save();
    $db->rollback();
  }

  public function testSave()
  {
    $db = get_test_context()->db;

    $stub = $this->getStub();
    $stub->class = 'dummy';
    $stub->name = 'test';

    $db->beginTransaction();
    $saved = $stub->save();

    $this->assertTrue($saved instanceof NodeStub);
    $this->assertEquals($saved, $stub);

    $this->assertNotEquals(null, $stub->id);

    $data = $db->getResults("SELECT * FROM `node` WHERE `id` = ?", array($stub->id));
    $this->assertTrue(is_array($data));
    $this->assertEquals('test', $data[0]['name']);
    $this->assertEquals('dummy', $data[0]['class']);

    $db->rollback();
  }

  public function testOnSave()
  {
    $db = get_test_context()->db;

    $stub = $this->getStub();
    $stub->class = 'dummy';
    $stub->name = 'test';
    $stub->onSave("INSERT INTO `node__rel` (`tid`, `nid`) VALUES (%ID%, 666)");

    $db->beginTransaction();
    $stub->save();
    $data = $db->getResult("SELECT `nid` FROM `node__rel` WHERE `tid` = ?", array($stub->id));
    $db->rollback();

    $this->assertEquals(666, $data);
  }

  public function testDelete()
  {
    $db = get_test_context()->db;

    $stub = $this->getStub();
    $stub->class = 'dummy';
    $stub->name = 'test';

    $db->beginTransaction();
    $id = $stub->save()->id;
    $this->assertNotEquals(null, $stub->save());

    $stub->delete();
    $data = $db->getResult("SELECT 1 FROM `node` WHERE `id` = ? AND `deleted` = 0", array($id));
    $this->assertEquals(null, $data);

    $stub->undelete();
    $data = $db->getResult("SELECT 1 FROM `node` WHERE `id` = ? AND `deleted` = 0", array($id));
    $this->assertEquals(1, $data);

    $db->rollback();
  }

  public function testPublish()
  {
    $db = get_test_context()->db;
    $stub = $this->getStub();

    $db->beginTransaction();

    $stub->class = 'dummy';
    $stub->name = 'test';
    $stub->save();

    $data = $db->getResult("SELECT `published` FROM `node` WHERE `id` = ?", array($stub->id));
    $this->assertEquals(0, $data);

    $stub->publish();
    $data = $db->getResult("SELECT `published` FROM `node` WHERE `id` = ?", array($stub->id));
    $this->assertEquals(1, $data);

    $stub->unpublish();
    $data = $db->getResult("SELECT `published` FROM `node` WHERE `id` = ?", array($stub->id));
    $this->assertEquals(0, $data);

    $db->rollback();
  }

  public function testGetParents()
  {
    $db = get_test_context()->db;
    $id = $db->getResult("SELECT `id` FROM `node` WHERE `class` = 'tag' AND `parent_id` IS NOT NULL AND `deleted` = 0 LIMIT 1");

    $stub = NodeStub::create($id, $db);
    $parents = $stub->getParents();

    $this->assertTrue(count($parents) > 1);
    $this->assertEquals($stub->id, array_pop($parents)->id);
  }

  public function testGetLinked()
  {
    $stub = $this->getStub();

    $list = $stub->getLinked();
    $this->assertTrue(is_array($list));
    $this->assertTrue(empty($list));

    $stub = $this->getRootTag();
    $list = $stub->getLinked();
    $this->assertTrue(is_array($list));
    $this->assertFalse(empty($list));

    $child = $list[0];

    $list = $child->getLinkedTo();
    $this->assertTrue(is_array($list));
    $this->assertFalse(empty($list));
    $this->assertEquals($stub->id, $list[0]->id);

    $list = $child->getLinkedTo($stub->class);
    $this->assertTrue(is_array($list));
    $this->assertFalse(empty($list));
    $this->assertEquals($stub->id, $list[0]->id);

    $list = $child->getLinkedTo($stub->class, true);
    $this->assertTrue(is_array($list));
    $this->assertFalse(empty($list));
    $this->assertEquals($stub->id, $list[0]);
  }

  public function testGetName()
  {
    $stub = $this->getStub();
    $this->assertEquals(null, $stub->getName());

    $stub->name = 'test';
    $this->assertEquals('test', $stub->getName());
  }

  public function testStack()
  {
    $stub = $this->getStub();
    $xml = $stub->push();
    $this->assertEquals(null, $xml);

    $stub = $this->getRootTag();
    $xml = $stub->push();
    $this->assertTrue(0 === strpos($xml, '<node '));

    $this->assertEquals(null, NodeStub::getStack());
  }

  public function testGetClassName()
  {
    $this->assertEquals('Node', NodeStub::getClassName('dummy'));
    $this->assertEquals('TagNode', NodeStub::getClassName('tag'));
  }

  public function testGetObject()
  {
    $this->assertEquals('Node', get_class($this->getStub()->getObject()));
    $this->assertEquals('TagNode', get_class($this->getRootTag()->getObject()));
  }

  /**
   * @expectedException RuntimeException
   */
  public function testSerialize()
  {
    serialize($this->getStub());
  }

  public function testGetChildrenOf()
  {
    $db = get_test_context()->db;

    $list = NodeStub::getChildrenOf($db, 'dummy');
    $this->assertTrue(is_array($list));
    $this->assertTrue(empty($list));

    $list = NodeStub::getChildrenOf($db, 'tag', $this->getRootTag()->id);
    $this->assertTrue(is_array($list));
    $this->assertFalse(empty($list));
  }

  public function testLoadByName()
  {
    $root = $this->getRootTag();
    $this->assertNotEquals(null, $root->id);
  }
}
