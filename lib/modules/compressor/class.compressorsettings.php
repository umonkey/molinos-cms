<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CompressorSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Настройка компрессора'),
      ));

    $form->addControl(new BoolControl(array(
      'value' => 'config_strip_html',
      'label' => t('Очищать HTML код'),
      )));

    return $form;
  }

  private static function getGroups()
  {
    $result = array();

    foreach (Node::find(array('class' => 'group')) as $g)
      $result[$g->id] = $g->name;

    asort($result);

    return $result;
  }

  public static function hookPostInstall()
  {
  }
}
