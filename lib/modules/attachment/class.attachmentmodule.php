<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AttachmentModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    $att = new Attachment($ctx);
    $att->sendFile();
  }

  public static function rpc_find(Context $ctx)
  {
    $nodes = Node::find(array(
      'class' => 'file',
      'name' => '%'. trim($ctx->get('search')) .'%',
      ), 5);

    $odd = true;
    $output = '';

    foreach ($nodes as $node) {
      $c1 = mcms::html('input', array(
        'type' => 'radio',
        'name' => $ctx->get('name', 'unknown') .'[id]',
        'value' => $node->id,
        ));

      $c2 = mcms::html('img', array(
        'alt' => $node->filename,
        'width' => 50,
        'height' => 50,
        'src' => '?q=attachment.rpc&fid='. $node->id
          .',50,50,cw'
        ));

      $c3 = t('<a target=\'blank\' href=\'@url\'>%name</a><br />Размер: %size<br />Тип: %type', array(
        '@url' => '?q=attachment/'. $node->id
          .'/'. urlencode($node->filename),
        '%name' => $node->name,
        '%size' => $node->filesize,
        '%type' => $node->filetype,
        ));

      $row = mcms::html('td', array('class' => 'check'), $c1);
      $row .= mcms::html('td', $c2);
      $row .= mcms::html('td', array('class' => 'info'), $c3);

      $output .= mcms::html('tr', array(
        'class' => $odd ? 'odd' : 'even',
        ), $row);

      $odd = !$odd;
    }

    mcms::fixurls(mcms::html('table', array(
      'class' => 'options',
      ), $output), true);
  }

  public static function rpc_ftp(Context $ctx)
  {
    if (null == ($path = mcms::config('ftpfolder')))
      return t('FTP архив не настроен.  Для его использования нужно в '
        .'конфигурационном файле, в параметре ftpfolder, '
        .'указать путь к папке, в которую пользователи загружают файлы.  '
        .'После этого файлы из этой папки можно будет использовать в CMS.');

    $files = FileNode::listFilesOnFTP();

    $type = preg_match('@^file_\d+$@', $ctx->get('name'))
      ? 'checkbox'
      : 'radio';

    if (empty($files))
      return t('Нет файлов, загруженных по FTP.');

    $odd = true;
    $output = mcms::html('tr', t('<th>&nbsp;</th><th>Имя</th><th>Размер</th>'));

    foreach ($files as $k => $v) {
      $c1 = mcms::html('input', array(
        'type' => $type,
        'name' => $ctx->get('name') .'[ftp][]',
        'value' => $k,
        ));
      $c2 = htmlspecialchars($k);
      $c3 = filesize($path .'/'. $k) .'Б';

      $row = mcms::html('td', array(
        'class' => 'checkbox',
        ), $c1)
        . mcms::html('td', array(
          'class' => 'name',
          ), $c2)
        . mcms::html('td', array(
          'class' => 'size',
          ), $c3);

      $output .= mcms::html('tr', array(
        'class' => $odd ? 'odd' : 'even',
        ), $row);

      $odd = !$odd;
    }

    $message = t('Следующие файлы были загружены по FTP:');

    mcms::fixurls($message . mcms::html('table', array(
      'class' => 'options',
      ), $output), true);
  }
};
