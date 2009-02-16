<?php

class BackupRPC extends RPCHandler implements iRemoteCall
{
  public static function rpc_post_backup(Context $ctx)
  {
    return new Redirect('?q=backup.rpc&action=download');
  }

  public static function rpc_get_download(Context $ctx)
  {
    zip::fromFolder($zipFile = os::path($ctx->config->getPath('tmpdir'), 'backup.zip'), MCMS_ROOT,
      realpath($ctx->config->getPath('tmpdir')));

    $filename = $ctx->host() . '-' . date('YmdHi', time() - date('Z', time())) . '.zip';

    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($zipFile));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($zipFile);
    unlink($zipFile);
    die();
  }
}
