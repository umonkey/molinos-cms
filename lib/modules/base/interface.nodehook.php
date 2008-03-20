<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

// Интерфейс для обработки операций над документами.
interface iNodeHook
{
  public static function hookNodeUpdate(Node $node, $op);
};
