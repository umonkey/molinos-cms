<?php

class BackupRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    switch ($ctx->post('action')) {
    case 'backup':
      zip::fromFolder($zipFile = os::path('.', mcms::config('tmpdir'), 'backup.zip'), MCMS_ROOT,
        realpath(mcms::config('tmpdir')));

      header('Content-Type: application/zip');
      header('Content-Length: ' . filesize($zipName));
      header('Content-Disposition: attachment; filename="backup.zip"');
      readfile($zipFile);
      unlink($zipFile);
      die();
    }
  }
}
