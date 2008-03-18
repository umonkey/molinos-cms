<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iFormControl
{
  public static function getInfo();
  public static function getSQL();
  public function getHTML(array $data);
  public function validate(array $data);
  public function addControl(Control $ctl);
  public function findControl($value);
};
