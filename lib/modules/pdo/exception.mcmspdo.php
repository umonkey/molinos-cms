<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class McmsPDOException extends PDOException
{
  private $params = null;

  public function __construct(PDOException $e, $sql = null, $params = null)
  {
    $this->code = $e->getCode();
    $this->params = $params;
    $this->message = $e->getMessage();

    if (($ctx = Context::last()) and $ctx->canDebug())
      $this->message .= '; SQL: '. $sql;
  }
};
