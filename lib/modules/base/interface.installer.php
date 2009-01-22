<?php

interface iInstaller
{
  public static function onInstall(Context $ctx);

  public static function onUninstall(Context $ctx);
}
