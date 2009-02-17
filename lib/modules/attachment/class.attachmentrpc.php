<?php

class AttachmentRPC extends RPCHandler implements iRemoteCall
{
  public static function rpc_get_default(Context $ctx)
  {
    if (null === ($fid = $ctx->get('fid')))
      $fid = trim(strchr($ctx->query(), '/'), '/');

    $node = NodeStub::create($fid, $ctx->db);
    $path = os::webpath($ctx->config->getDirName(), $ctx->config->files, $node->filepath);

    return new Redirect($path, Redirect::PERMANENT);
  }
}
