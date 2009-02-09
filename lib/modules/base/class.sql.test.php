<?php

class SqlTests extends PHPUnit_Framework_TestCase
{
  public function testInEmpty()
  {
    $params = array();
    $sql = sql::in(array(), $params);
    $this->assertEquals('IS NULL', $sql);
    $this->assertTrue(empty($params));
  }

  public function testInNull()
  {
    $params = array();
    $sql = sql::in(null, $params);
    $this->assertEquals('IS NULL', $sql);
    $this->assertTrue(empty($params));
  }

  public function testInSimple()
  {
    $params = array();
    $sql = sql::in(123, $params);
    $this->assertEquals('= ?', $sql);
    $this->assertEquals(array(123), $params);
  }

  public function testInSingle()
  {
    $params = array();
    $sql = sql::in(array(123), $params);
    $this->assertEquals('= ?', $sql);
    $this->assertEquals(array(123), $params);
  }

  public function testInMultiple()
  {
    $params = array();
    $values = array(1, 2, 3);
    $sql = sql::in($values, $params);
    $this->assertEquals('IN (?, ?, ?)', $sql);
    $this->assertEquals($values, $params);
  }

  public function testInRepeated()
  {
    $params = array();
    $sql = sql::in(array(1, 1, 2, 2), $params);
    $this->assertEquals('IN (?, ?)', $sql);
    $this->assertEquals(array(1, 2), $params);
  }

  public function testGetUpdate()
  {
    $data = array(
      'id' => 1,
      'name' => 'test',
      'value' => 'ok',
      );

    list($sql, $params) = sql::getUpdate('table', $data, 'id');

    $this->assertEquals('UPDATE `table` SET `name` = ?, `value` = ? WHERE `id` = ?', $sql);
    $this->assertEquals(array('test', 'ok', 1), $params);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testGetUpdateBadKey()
  {
    $data = array(
      'id' => 1,
      'name' => 'test',
      );

    sql::getUpdate('table', $data, 'xyz');
  }

  public function testGetInsert()
  {
    list($sql, $params) = sql::getInsert('table', array(
      'id' => 1,
      'name' => 'test',
      ));
    $this->assertEquals('INSERT INTO `table` (`id`, `name`) VALUES (?, ?)', $sql);
    $this->assertEquals(array(1, 'test'), $params);
  }
}
