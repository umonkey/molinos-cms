<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DrawTextModule implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $fonts = array();

    foreach (Node::find(array('class' => 'file', 'filetype' => 'application/x-font-ttf')) as $n)
      $fonts[$n->id] = isset($n->name) ? $n->name : $n->filename;

    $form->addControl(new EnumControl(array(
      'value' => 'config_font',
      'label' => t('Шрифт по умолчанию'),
      'default' => t('(не использовать)'),
      'options' => $fonts,
      'description' => t('Вы можете <a href=\'@url\'>загрузить новый шрифт</a> в файловый архив.', array(
        '@url' => '?q=admin/content/create&type=file&destination=CURRENT',
        )),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }
};
