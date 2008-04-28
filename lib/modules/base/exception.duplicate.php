<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DuplicateException extends UserErrorException
{
  public function __construct($message)
  {
    parent::__construct("Нарушение уникальности", 400, "Нарушение уникальности", $message);
  }
};
