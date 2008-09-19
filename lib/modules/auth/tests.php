<?php

class AuthModuleTests extends PHPUnit_Framework_TestCase
{

  public function testInit()
  {
    copy(MCMS_ROOT.'/conf/default.db', MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default.ini', MCMS_ROOT.'/conf/default_backup.ini');
    $config = Config::getInstance();
    $config->set('default','sqlite:conf/test.db','db');
    PDO_Singleton::getInstance('default', true);
  }

  public function testAuthorize()
  {
    $node = Node::create('user', array(
      'parent_id' => null,
      'name' => 'tt@mail.ru',
       ));
    $node->password = '123';
    $node->save();
    $node->publish();

    $this->assertTrue(!empty($node->id));
    User::authorize('tt@mail.ru','123');

    $node->delete();
  }

  public function testRestore()
  {
    unlink(MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default_backup.ini', MCMS_ROOT.'/conf/default.ini');
    unlink(MCMS_ROOT.'/conf/default_backup.ini');
    $config = Config::getInstance();
    $config->set('default','sqlite:conf/default.db','db');
    PDO_Singleton::getInstance('default', true);
  }

}

