<?php

class ExchangeModuleTests extends PHPUnit_Framework_TestCase
{
  public function testInit()
  {
    copy(MCMS_ROOT.'/conf/default.db', MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default.ini', MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite:conf/test.db','db');
    PDO_Singleton::getInstance('default', true);
  }

  public function testExportImport()
  {
    $xml = ExchangeModule::export('testprofile', 'testprofile');
    $this->assertTrue(!empty($xml));

    $config = BebopConfig::getInstance();
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
    unlink(MCMS_ROOT.'/conf/test.db');
    copy(MCMS_ROOT.'/conf/default_backup.ini', MCMS_ROOT.'/conf/default.ini');
    unlink(MCMS_ROOT.'/conf/default_backup.ini');
    $config = BebopConfig::getInstance();
    $config->set('default','sqlite:conf/default.db','db');
    PDO_Singleton::getInstance('default', true);
  }
}
