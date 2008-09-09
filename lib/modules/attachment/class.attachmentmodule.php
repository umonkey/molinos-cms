<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AttachmentModule implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    $att = new Attachment($ctx);
    $att->sendFile();
  }
};
