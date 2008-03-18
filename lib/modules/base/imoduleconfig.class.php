<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iModuleConfig
{
  public static function formGetModuleConfig();
  public static function hookPostInstall();
};
