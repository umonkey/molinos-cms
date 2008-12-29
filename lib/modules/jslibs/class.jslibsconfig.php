<?php

class JSLibsConfig implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_use',
      'label' => t('Использовать библиотеки'),
      'options' => self::getLibrariesHTML(),
      'description' => t('Библиотеки загружаются со специальных <a href="@url">выделенных серверов Google</a>, что очень быстро и хорошо кэшируется.', array(
        '@url' => 'http://code.google.com/intl/ru-RU/apis/ajaxlibs/',
        )),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  private static function getLibrariesHTML()
  {
    $result = array();

    foreach (self::getLibraries() as $k => $v)
      $result[$k] = l($v['home'], $k) . ' v' . $v['version'];

    return $result;
  }

  public static function getLibraries()
  {
    return array(
      'jQuery' => array(
        'version' => '1.2.6',
        'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js',
        'home' => 'http://jquery.com/',
        ),
      'jQuery UI' => array(
        'version' => '1.5.3',
        'url' => 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.5.3/jquery-ui.min.js',
        'home' => 'http://ui.jquery.com/',
        ),
      'Prototype' => array(
        'version' => '1.6.0.3',
        'url' => 'http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.3/prototype.js',
        'home' => 'http://www.prototypejs.org/',
        ),
      'script.aculo.us' => array(
        'version' => '1.8.2',
        'url' => 'http://ajax.googleapis.com/ajax/libs/scriptaculous/1.8.2/scriptaculous.js',
        'home' => 'http://script.aculo.us/',
        ),
      'MooTools' => array(
        'version' => '1.2.1',
        'url' => 'http://ajax.googleapis.com/ajax/libs/mootools/1.2.1/mootools-yui-compressed.js',
        'home' => 'http://mootools.net/',
        ),
      'Dojo' => array(
        'version' => '1.2.3',
        'url' => 'http://ajax.googleapis.com/ajax/libs/dojo/1.2.3/dojo/dojo.xd.js',
        'home' => 'http://dojotoolkit.org/',
        ),
      'SWFObject' => array(
        'version' => '2.1',
        'url' => 'http://ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js',
        'home' => 'http://code.google.com/p/swfobject/',
        ),
      'YUI' => array(
        'version' => '2.6.0',
        'url' => 'http://ajax.googleapis.com/ajax/libs/yui/2.6.0/build/yuiloader/yuiloader-min.js',
        'home' => 'http://developer.yahoo.com/yui/',
        ),
      );
  }
}
