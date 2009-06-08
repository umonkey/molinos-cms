<?php

class S3API
{
  /**
   * Добавляет к файлам действие «Загрузить в S3».
   * @mcms_message ru.molinos.cms.node.actions
   */
  public static function on_get_actions(Context $ctx, Node $node)
  {
    $result = array();

    if ('file' == $node->class) {
      if (self::canUploadToS3($node))
        $result['upload2s3'] = array(
          'title' => t('Загрузить в S3'),
          'href' => 'admin/service/s3/move?node=' . $node->id
            . '&destination=CURRENT',
          );
    }

    return $result;
  }

  /**
   * Перемещает файл в S3.
   */
  public static function on_move_to_s3(Context $ctx)
  {
    $node = Node::load($ctx->get('node'), $ctx->db);

    if (file_exists($fileName = $node->getRealURL())) {
      if ($url = self::moveFileToS3($fileName)) {
        $ctx->db->beginTransaction();
        $node->remoteurl = $url;
        $node->save();
        $ctx->db->commit();

        unlink($fileName);
      }
    }

    return $ctx->getRedirect();
  }

  /**
   * Проверяет, можно ли загрузить файл в S3.
   */
  private static function canUploadToS3(FileNode $node)
  {
    if (empty($node->remoteurl))
      return true;

    $host = url::host($node->remoteurl);
    if ('.s3.amazonaws.com' == substr($host, -17))
      return false;

    return true;
  }

  /**
   * Загружает файл в S3.
   */
  public static function moveFileToS3($fileName, $mimeType = null)
  {
    self::checkEnv($ctx = Context::last());

    $conf = $ctx->config->get('modules/s3');
    $s3 = new S3($conf['accesskey'], $conf['secretkey']);

    $bucketName = trim($ctx->config->get('modules/s3/bucket', 'files'), '/');
    /*
    if (!in_array($bucketName, $s3->listBuckets()))
      throw new RuntimeException(t('Нет такой папки: ' . $bucketName));
    */

    if ($f = fopen($fileName, 'rb')) {
      if (!$r = S3::inputResource($f, filesize($fileName)))
        throw new RuntimeException(t('Не удалось создать ресурс из файла %filename.', array(
          '%filename' => $fileName,
          )));

      if (!($response = S3::putObject($r, $bucketName, basename($fileName), S3::ACL_PUBLIC_READ)))
        throw new RuntimeException(t('Не удалось загрузить файл %filename в папку %bucket.', array(
          '%filename' => $fileName,
          '%bucket' => $bucketName,
          )));

      $url = 'http://' . $bucketName . '.s3.amazonaws.com/' . basename($fileName);
      mcms::flog('S3: ' . $url);
      return $url;
    }
  }

  /**
   * Проверяет окружение.
   */
  private static function checkEnv(Context $ctx)
  {
    if (!function_exists('curl_init'))
      throw new RuntimeException(t('Для работы с S3 нужно расширение CURL.'));
  }
}
