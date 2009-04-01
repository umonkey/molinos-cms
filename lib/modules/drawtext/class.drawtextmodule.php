<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DrawTextModule
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/drawtext',
        'title' => t('Рисование текста на картинках'),
        'method' => 'modman::settings',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.drawtext
   */
  public static function on_get_settings(Context $ctx)
  {
    $fonts = array();
    foreach (Node::find(Context::last()->db, array('class' => 'file', 'filetype' => 'application/x-font-ttf')) as $n)
      $fonts[$n->id] = isset($n->name) ? $n->name : $n->filename;

    return new Schema(array(
      'font' => array(
        'type' => 'EnumControl',
        'label' => t('Шрифт по умолчанию'),
        'default' => t('(не использовать)'),
        'options' => $fonts,
        'description' => t('Вы можете <a href=\'@url\'>загрузить новый шрифт</a> в файловый архив.', array(
          '@url' => '?q=admin/content/create&type=file&destination=CURRENT',
          )),
        ),
      ));
  }
};
