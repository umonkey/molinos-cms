<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Обращение к безвиджетным модулям.
interface iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx);
};
