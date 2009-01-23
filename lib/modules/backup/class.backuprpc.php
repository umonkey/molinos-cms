<?php

class BackupRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_post_backup(Context $ctx)
  {
    return new Redirect('?q=backup.rpc&action=download');
  }

  public static function rpc_post_download(Context $ctx)
  {
    zip::fromFolder($zipFile = os::path('.', mcms::config('tmpdir'), 'backup.zip'), MCMS_ROOT,
      realpath(mcms::config('tmpdir')));

    $filename = $ctx->host() . '-' . date('Y-m-d', time() - date('Z', time())) . '.zip';

    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($zipName));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($zipFile);
    unlink($zipFile);
    die();
  }
}
