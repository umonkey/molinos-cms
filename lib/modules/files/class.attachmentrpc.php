<?php

class AttachmentRPC extends RPCHandler
{
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function rpc_get_default(Context $ctx)
  {
    if (null === ($fid = $ctx->get('fid')))
      $fid = trim(strchr($ctx->query(), '/'), '/');

    $node = Node::load($fid, $ctx->db);
    $path = os::webpath($ctx->config->getDirName(), $ctx->config->files, $node->filepath);

    return new Redirect($path, Redirect::PERMANENT);
  }

  /**
   * Добавление нескольких файлов (форма).
   */
  public static function on_get_form(Context $ctx, $path, array $pathinfo)
  {
    $content = $help = '';

    $next = '?destination=' . urlencode($ctx->get('destination'));
    $next .= '&sendto=' . urlencode($ctx->get('sendto'));

    $options = array(
      'name' => 'addfile',
      'title' => t('Добавление файлов в архив'),
      'mode' => $pathinfo['mode'],
      'target' => $path . $next,
      'back' => $ctx->get('destination'),
      '#text' => '',
    );

    $options['#text'] .= self::get_modes($ctx, $pathinfo['mode']);

    switch ($mode = $pathinfo['mode']) {
    case 'remote':
      $options['title'] = t('Добавление файлов с другого сайта', array(
        '@url' => 'admin/content/files',
        ));
      $help = t('<p>Введите ссылки на файлы, включая префикс «http://».  Файлы могут быть любого размера.</p>');
      break;
    case 'normal':
      $help = t('<p>Размер одного загружаемого файла не должен превышать %limit1, а суммарный размер всех загружаемых файлов не должен превышать %limit2.</p>', array(
        '%limit1' => ini_get('upload_max_filesize'),
        '%limit2' => ini_get('post_max_size'),
        ));
      break;
    }

    $options['#text'] .= html::wrap('help', html::cdata($help));

    return html::em('content', $options, $content);
  }

  /**
   * Добавление нескольких файлов (обработка).
   */
  public static function on_post_form(Context $ctx)
  {
    $data = $ctx->post('files', array());
    $keys = array_keys($data);

    $files = array();

    if (empty($data['name']) or !is_array($data['name']))
      throw new BadRequestException(t('Не выбраны файлы для загрузки.'));

    for ($i = 0; isset($data['name'][$i]); $i++)
      foreach ($keys as $key)
        if (!empty($data[$key][$i]['tmp_name']))
          $files[$i][$key] = $data[$key][$i];

    return self::add_files($ctx, $files);
  }

  /**
   * Добавление файлов с FTP (форма).
   */
  public static function on_get_ftp_form(Context $ctx)
  {
    if (!($files = self::getFtpFiles($ctx)))
      throw new PageNotFoundException();

    $options = array(
      'name' => 'addfile',
      'title' => t('Добавление <a href="@url">файлов</a>, загруженных по FTP', array(
        '@url' => 'admin/content/files',
        )),
      'mode' => 'ftp',
      'target' => 'admin/create/file/ftp?destination=' . urlencode($ctx->get('destination')),
      'back' => $ctx->get('destination'),
    );

    $content = '';
    foreach ($files as $file)
      $content .= html::em('file', array(
        'name' => basename($file),
        'size' => filesize($file),
        'sizeh' => mcms::filesize(filesize($file)),
        'time' => mcms::now(filemtime($file)),
        ));

    $content .= self::get_modes($ctx, 'ftp');

    return html::em('content', $options, $content);
  }

  /**
   * Добавление файлов с FTP (обработка).
   */
  public static function on_post_ftp(Context $ctx)
  {
    $files = array();

    $path = os::path(MCMS_SITE_FOLDER, $ctx->config['modules']['files']['ftp']);
    $remove = !$ctx->post('preserve');

    foreach ($ctx->post('files') as $fileName) {
      $files[] = array(
        'tmp_name' => os::path($path, $fileName),
        'remove' => $remove,
        );
    }

    return self::add_files($ctx, $files);
  }

  /**
   * Добавление файлов с удалённого сервера (обработка).
   */
  public static function on_post_remote(Context $ctx)
  {
    $files = array();

    foreach ($ctx->post('files') as $url) {
      if (!empty($url)) {
        $head = http::head(str_replace(' ', '+', $url));

        if ($head['_status'] == 200) {
          $file = array(
            'type' => $head['Content-Type'],
            'size' => $head['Content-Length'],
            'remove' => true,
            'url' => $url,
            );

          if (!$ctx->post('symlink'))
            $file['tmp_name'] = http::fetch($url);

          $tmp = parse_url($url);
          $file['name'] = basename($tmp['path']);

          if ('application/octet-stream' == $file['type'] and !empty($file['tmp_name']))
            $file['type'] = os::getFileType($file['tmp_name'], $file['name']);

          $files[] = $file;
        }
      }
    }

    return self::add_files($ctx, $files);
  }

  /**
   * Редактирование нескольких файлов (форма).
   */
  public static function on_get_edit_form(Context $ctx)
  {
    $nodes = Node::findXML(array(
      'class' => 'file',
      'id' => explode(' ', $ctx->get('files')),
      'deleted' => 0,
      ));

    return html::em('content', array(
      'name' => 'editfiles',
      'title' => t('Редактирование файлов'),
      'action' => 'admin/files/edit?destination=' . urlencode($ctx->get('destination'))
        . '&sendto=' . urlencode($ctx->get('sendto')),
      'path' => os::webpath(MCMS_SITE_FOLDER, $ctx->config->get('modules/files/storage')),
      'ids' => $ctx->get('files'),
      ), $nodes);
  }

  /**
   * Редактирование нескольких файлов (обработка).
   */
  public static function on_post_edit_form(Context $ctx)
  {
    if ($ctx->get('redir')) {
      $next = 'admin/files/edit?files=' . implode('+', $ctx->post('selected'))
        . '&destination=' . urlencode($ctx->get('destination'));
      $ctx->redirect($next);
    }

    $labels = preg_split('/,\s+/', $ctx->post('labels'), -1, PREG_SPLIT_NO_EMPTY);

    $ctx->db->beginTransaction();

    foreach ($ctx->post('files', array()) as $nid => $info) {
      $node = Node::load($nid, $ctx->db);
      $node->name = $info['name'];
      $node->labels = array_merge($labels, preg_split('/,\s+/', $info['labels'], -1, PREG_SPLIT_NO_EMPTY));
      $node->save();
    }

    if ($to = $ctx->get('sendto'))
      $node = Node::load($to)->link(array_keys($ctx->post('files')), false)->save();

    $ctx->db->commit();

    return $ctx->getRedirect('admin/content/files');
  }

  /**
   * Обработка нескольких файлов.
   */
  private static function add_files(Context $ctx, array $files)
  {
    $bad = 0;
    $good = array();

    $ctx->db->beginTransaction();

    foreach ($files as $file) {
      try {
        $good[] = Node::create('file', $ctx->db)->import($file)->save()->id;
      } catch (Exception $e) {
        $bad = 1;
      }
    }

    if (count($good))
      $ctx->db->commit();

    $next = 'admin/files/edit'
      . '?destination=' . urlencode($ctx->get('destination'))
      . '&files=' . implode('+', $good);

    if ($bad)
      $next .= '&bad=' . $bad;
    $next .= '&sendto=' . $ctx->get('sendto');

    $ctx->redirect($next);
  }

  /**
   * Возвращает доступные режимы загрузки файлов.
   */
  private static function get_modes(Context $ctx, $mode)
  {
    $result = '';

    $next = array();
    if ($tmp = $ctx->get('sendto'))
      $next[] = 'sendto=' . urlencode($tmp);
    if ($tmp = $ctx->get('destination'))
      $next[] = 'destination=' . urlencode($tmp);
    $next = empty($next)
      ? ''
      : '?' . implode('&', $next);

    if ($mode != 'normal')
      $result .= html::em('mode', array(
        'name' => 'normal',
        'href' => 'admin/create/file' . $next,
        ), html::cdata(t('со своего компьютера')));
    if ($mode != 'remote')
      $result .= html::em('mode', array(
        'name' => 'remote',
        'href' => 'admin/create/file/remote' . $next,
        ), html::cdata(t('с другого веб-сайта')));
    if ($mode != 'ftp' and self::getFtpFiles($ctx))
      $result .= html::em('mode', array(
        'name' => 'ftp',
        'href' => 'admin/create/file/ftp' . $next,
        ), html::cdata(t('по FTP')));

    $result .= html::em('mode', array(
      'name' => 'archive',
      'href' => 'admin/content/files' . $next,
      ), html::cdata(t('найти нужные файлы в архиве')));

    return $result;
  }

  /**
   * Обновление иконок файлов.
   * Сейчас просто пересохраняет файлы, чтобы сгенерировать все версии.
   */
  public static function on_update_icons(Context $ctx)
  {
    $ids = explode(' ', $ctx->get('files'));

    if (empty($ids))
      throw new BadRequestException(t('Не указаны идентификаторы файлов (GET-параметр files).'));

    $ctx->db->beginTransaction();
    foreach ($ids as $id)
      Node::load($id, $ctx->db)->touch()->save();
    $ctx->db->commit();

    return $ctx->getRedirect();
  }

  /**
   * Проверяет, возможна ли работа с FTP.
   * Возвращает список доступных файлов.
   */
  private static function getFtpFiles(Context $ctx)
  {
    if (!$ftp = $ctx->config->get('modules/files/ftp'))
      return false;

    if (!is_dir($ftp = os::path(MCMS_SITE_FOLDER, $ftp)))
      return false;

    if (!($files = os::find(os::path($ftp, '*'))))
      return false;

    return $files;
  }

  /**
   * Скачивание файлов.
   */
  public static function on_download(Context $ctx, $path, array $pathinfo, $node, $filename)
  {
    $node = Node::load(array(
      'class' => 'file',
      'deleted' => 0,
      // 'published' => 1,
      'id' => $node,
      ), $ctx->db);

    if (!($url = $node->remoteurl) and !file_exists($url = $node->getRealURL()))
      throw new PageNotFoundException();

    $ctx->registry->broadcast('ru.molinos.cms.log.access', array($ctx, $node));

    return new Redirect($url, Redirect::TEMPORARY);
  }

  /**
   * Открепляет файл от документа.
   */
  public static function on_unlink_from_node(Context $ctx)
  {
    $ctx->db->beginTransaction();
    $ctx->db->exec("DELETE FROM `node__rel` WHERE `tid` = ? AND `nid` = ? AND `key` = ?",
      array($ctx->get('node'), $ctx->get('file'), $ctx->get('field')));
    // Пересохраняем ноду, чтобы сработали обработчики изменения, вроде индексатора.
    Node::load($ctx->get('node'), $ctx->db)->touch()->save();
    $ctx->db->commit();

    return $ctx->getRedirect();
  }
}
