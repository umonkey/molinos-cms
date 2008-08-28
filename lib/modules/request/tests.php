<?php

class RequestModuleTests extends PHPUnit_Framework_TestCase
{
  public function testInit()
  {
    copy(MCMS_ROOT.'/conf/default.db', MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default.ini', MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite:conf/test.db','db');
    PDO_Singleton::getInstance('default', true);
  }

  public function testSetGlobal()
  {
    $node = Node::find(array('class' => 'domain'));
    $node = array_pop($node);
    $this->assertTrue(!empty($node));
    $ctx = Context::setGlobal(null, null, $node);
    $this->assertTrue(!empty($ctx));
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
