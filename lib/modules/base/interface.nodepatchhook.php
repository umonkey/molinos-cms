<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iNodePatchHook
{
  public static function patchNode(Node $node);
  public static function patchNodeList(array &$nodes);
};
