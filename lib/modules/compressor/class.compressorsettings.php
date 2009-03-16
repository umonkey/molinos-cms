<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CompressorSettings
{
  /**
   * @mcms_message ru.molinos.cms.admin.config.module.compressor
   */
  public static function formGetModuleConfig()
  {
    return new Schema(array(
      'strip_html' => array(
        'type' => 'BoolControl',
        'label' => t('Очищать HTML код'),
        ),
      ));
  }

  private static function getGroups()
  {
    $result = array();

    foreach (Node::find(Context::last()->db, array('class' => 'group')) as $g)
      $result[$g->id] = $g->name;

    asort($result);

    return $result;
  }
}
