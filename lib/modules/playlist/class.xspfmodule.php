<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class XspfModule implements iRemoteCall, iModuleConfig
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (!count($nids = explode(',', $ctx->get('nodes'))))
      throw new InvalidArgumentException('Nodes not specified.');

    $output = '';
    $tracks = array();

    foreach ($nodes = Node::find(array('class' => 'file', 'id' => $nids)) as $node) {
      $track = mcms::html('title', $node->name);
      $track .= mcms::html('location', 'http://'. $_SERVER['HTTP_HOST'] .'attachment/'. $node->id .'?'. $node->filename);
      $tracks[] = mcms::html('track', $track);
    }

    if (empty($tracks))
      throw new PageNotFoundException();

    header('Content-Type: application/xspf+xml; charset=utf-8');

    // TODO: если запрошен один документ, и это — не файл, можно сразу возвращать все его файлы.

    $output .= "<?xml version='1.0' encoding='utf-8'?>";
    $output .= "<playlist version='1' xmlns='http://xspf.org/ns/0/'>";
    $output .= mcms::html('trackList', join('', $tracks));
    $output .= '</playlist>';

    header('Content-Length: '. strlen($output));
    die($output);
  }

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

  public static function hookPostInstall()
  {
  }
};
