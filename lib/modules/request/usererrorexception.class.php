<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UserErrorException extends Exception
{
  var $description = null;
  var $note = null;

  public function __construct($message, $code, $description = null, $note = null)
  {
    parent::__construct($message, $code);
    $this->description = $description;
    $this->note = $note;
  }

  public function getDescription()
  {
    return $this->description;
  }

  public function getNote()
  {
    return $this->note;
  }
};
