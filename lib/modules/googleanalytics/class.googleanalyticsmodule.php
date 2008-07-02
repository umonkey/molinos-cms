<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class GoogleAnalyticsModule implements iModuleConfig, iPageHook
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Интеграция с Google Analytics'),
      ));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_account',
      'label' => t('Учётная запись Google Analytics'),
      'description' => t('Получить учётную запись можно на сайте <a href=\'@url\'>Google Analytics</a>, выглядит она примерно так: UA-123456-1.', array(
        '@url' => 'http://www.google.com/analytics/',
        )),
      )));
    $form->addControl(new BoolControl(array(
      'value' => 'config_log_uids',
      'label' => t('Передавать имена пользователей'),
      'description' => t('При использовании этой опции Google будет получать имена залогиненных пользователей, что позволяет отделять их от анонимных.'),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
    $t = new TableInfo('node__log');

    $t->columnSet('lid', array(
      'type' => 'int',
      'required' => true,
      'key' => 'pri',
      'autoincrement' => true,
      ));
    $t->columnSet('timestamp', array(
      'type' => 'datetime',
      'key' => 'mul',
      'required' => true,
      ));
    $t->columnSet('nid', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('uid', array(
      'type' => 'int',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('username', array(
      'type' => 'varchar(255)',
      'required' => false,
      'key' => 'mul',
      ));
    $t->columnSet('ip', array(
      'type' => 'varchar(15)',
      'required' => true,
      'key' => 'mul',
      ));
    $t->columnSet('operation', array(
      'type' => 'varchar(10)',
      'key' => 'mul',
      ));
    $t->columnSet('message', array(
      'type' => 'TEXT',
      ));

    $t->commit();
  }

  public static function hookPage(&$output, Node $page)
  {
    $config = mcms::modconf('googleanalytics');

    if (!empty($config['account'])) {
      $html = '<script type=\'text/javascript\' src=\'http://www.google-analytics.com/urchin.js\'></script>';
      $html .= "<script type='text/javascript'>";
      $html .= "_uacct = '{$config['account']}';";

      if (!empty($config['log_uids']))
        $html .= "__utmSetVar('". mcms_plain(mcms::user()->login) ."');";

      $html .= "urchinTracker();";
      $html .= '</script>';

      $output = str_replace('</head>', $html .'</head>', $output);
    }
  }
};
