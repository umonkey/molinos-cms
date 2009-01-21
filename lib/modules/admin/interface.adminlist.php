<?php

interface iAdminList
{
  public function __construct(Context $ctx);

  public function getHTML($preset = null);
}
