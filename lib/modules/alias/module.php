<?php

class AliasModule implements iRequestHook
{
  public static function hookRequest(RequestContext $ctx = null)
  {
    if (null === $ctx) {
      if (null !== ($next = mcms::db()->getResult("SELECT `dst` FROM `node__path` WHERE `src` = :src", array(':src' => $_SERVER['REQUEST_URI']))))
        bebop_redirect($next, 301);
    }
  }
};
