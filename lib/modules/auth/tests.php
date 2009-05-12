<?php

class AuthModuleTests extends PHPUnit_Framework_TestCase
{
  public function testAuthorize()
  {
    $ctx = get_test_context();
    $db = $ctx->db;
    $db->beginTransaction();

    $node = Node::create('user', array(
      'parent_id' => null,
      'name' => 'tt@mail.ru',
       ));
    $node->setPassword('123');

    $node->save();
    $node->publish();

    $node->delete();

    $db->rollback();
  }
}
