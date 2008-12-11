<?php

class AutoUpdateRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);
  }

  public static function rpc_confirm(Context $ctx)
  {
    mcms::debug($ctx);
  }
}
