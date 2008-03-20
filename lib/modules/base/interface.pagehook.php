<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Интерфейс для обработки страниц.
interface iPageHook
{
  public static function hookPage(&$output, Node $page);
};
