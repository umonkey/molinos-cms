<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class XspfModule
{
  /**
   * @mcms_message ru.molinos.cms.rpc.playlist
   */
  public static function hookRemoteCall(Context $ctx)
  {
    if (!count($nids = explode(',', $ctx->get('nodes'))))
      throw new InvalidArgumentException('Nodes not specified.');

    $output = '';
    $tracks = array();

    foreach ($nodes = Node::find($ctx->db, array('class' => 'file', 'id' => $nids)) as $node) {
      $track = html::em('title', $node->name);
      $track .= html::em('location', 'http://'. $_SERVER['HTTP_HOST'] .'attachment/'. $node->id .'?'. $node->filename);
      $tracks[] = html::em('track', $track);
    }

    if (empty($tracks))
      throw new PageNotFoundException();

    // TODO: если запрошен один документ, и это — не файл, можно сразу возвращать все его файлы.

    $output .= "<?xml version='1.0' encoding='utf-8'?>";
    $output .= "<playlist version='1' xmlns='http://xspf.org/ns/0/'>";
    $output .= html::em('trackList', join('', $tracks));
    $output .= '</playlist>';

    return new Response($output, 'application/xspf+xml');
  }

  /**
   * @mcms_message ru.molinos.cms.admin.config.module.playlist
   */
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new SetControl(array(
      'value' => 'config_types',
      'label' => t('Типы обрабатываемых медиафайлов'),
      'options' => array(
        'mp3' => 'MP3',
        'flv' => 'Flash Video',
        ),
      )));

    $form->addControl(new SetControl(array(
      'value' => 'config_options',
      'label' => t('Дополнительные настройки'),
      'options' => array(
        'published' => t('Выводить только опубликованные файлы'),
        'info' => t('Добавлять ссылку на файл, для скачивания'),
        ),
      )));

    return $form;
  }
};
