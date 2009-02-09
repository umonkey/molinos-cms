<?php

class NodeQueryBuilderTests extends PHPUnit_Framework_TestCase
{
  public function testEmpty()
  {
    $nqb = new NodeQueryBuilder(array());
  }

  public function testGetCountQuery()
  {
    $nqb = new NodeQueryBuilder(array());

    $sql = null;
    $params = array();

    $nqb->getCountQuery($sql, $params);
    $this->assertEquals("SELECT COUNT(*) FROM `node` WHERE `node`.`lang` = 'ru' AND `node`.`deleted` = :param1", $sql);
    $this->assertEquals(array(
      ':param1' => 0,
      ), $params);
  }

  public function testGetSelectQuery()
  {
    $nqb = new NodeQueryBuilder(array());

    $sql = null;
    $params = array();

    $nqb->getSelectQuery($sql, $params);
    $this->assertEquals("SELECT * FROM `node` WHERE `node`.`lang` = 'ru' AND `node`.`deleted` = :param1", $sql);
    $this->assertEquals(array(
      ':param1' => 0,
      ), $params);

    $nqb = new NodeQueryBuilder(array(
      'class' => 'domain',
      'deleted' => 1,
      '#sort' => 'name',
      ));

    $sql = null;
    $params = array();

    $nqb->getSelectQuery($sql, $params);
    $this->assertEquals("SELECT * FROM `node` WHERE `node`.`lang` = 'ru' AND `node`.`class` = :param1 AND `node`.`deleted` = :param2 ORDER BY `node`.`name` ASC", $sql);
    $this->assertEquals(array(
      ':param1' => 'domain',
      ':param2' => 1,
      ), $params);
  }

  public function testGetClassName()
  {
    $nqb = new NodeQueryBuilder(array());
    $this->assertEquals(null, $nqb->getClassName());

    $nqb = new NodeQueryBuilder(array(
      'class' => 'type',
      ));
    $this->assertEquals('type', $nqb->getClassName());

    $nqb = new NodeQueryBuilder(array(
      'class' => array(
        'type',
        'domain',
        ),
      ));
    $this->assertEquals(null, $nqb->getClassName());
  }
}
