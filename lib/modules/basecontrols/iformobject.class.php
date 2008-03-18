<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iFormObject
{
  public function formGet();
  public function formGetData();
  public function formProcess(array $data);
};
