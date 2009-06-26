<?php

class BackupRPC
{
  public static function on_download(Context $ctx)
  {
    zip::fromFolder($zipFile = os::path($ctx->config->getPath('main/tmpdir'), 'backup.zip'), MCMS_ROOT,
      realpath($ctx->config->getPath('main/tmpdir')));

    $filename = $ctx->host() . '-' . date('YmdHi', time() - date('Z', time())) . '.zip';

    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($zipFile));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($zipFile);
    unlink($zipFile);
    die();
  }
}
