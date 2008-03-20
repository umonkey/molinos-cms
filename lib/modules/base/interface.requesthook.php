<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Интерфейс для обработки запросов.
interface iRequestHook
{
  public static function hookRequest(RequestContext $ctx = null);
};
