<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollNode extends Node implements iContentType
{
  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id) {
      $data['Visitors']['r'] = 1;
      $data['Content Managers']['r'] = 1;
      $data['Content Managers']['u'] = 1;
      $data['Content Managers']['d'] = 1;
    }

    return $data;
  }
};
