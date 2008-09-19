<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NotInstalledException extends UserErrorException
{
  private $type;

  public function __construct($type = null)
  {
    $this->type = $type;
    parent::__construct('Система не готова к использованию', 500);
  }

  public function get_type()
  {
    return $this->type;
  }
};
