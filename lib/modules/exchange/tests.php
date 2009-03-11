<?php

class ExchangeModuleTests extends PHPUnit_Framework_TestCase
{
  public function testInit()
  {
    $config = Config::getInstance();
    $config->set('default','sqlite:conf/test.db','db');
    PDO_Singleton::getInstance('default', true);
  }

  public function testExportImport()
  {
    $xml = ExchangeModule::export('testprofile', 'testprofile');
    $this->assertTrue(!empty($xml));

    $config = Config::getInstance();
    if (file_exists($dbfile = MCMS_ROOT.'/conf/exporttest.db'))
      unlink($dbfile);

    $config->set('default','sqlite:conf/exporttest.db','db');
    PDO_Singleton::getInstance('default', true);

    ExchangeModule::import($xml);
    $node = Node::find(array('class'=>'group','name'=>'Администраторы'));
    $node = array_pop($node);
    $this->assertTrue(!empty($node));

    if (file_exists($dbfile = MCMS_ROOT.'/conf/exporttest.db'))
      unlink($dbfile);
  }

  public function testRestore()
  {
    $config = Config::getInstance();
    $config->set('default','sqlite:conf/default.db','db');
    PDO_Singleton::getInstance('default', true);
  }
}
