<?php

class AttachmentListHandler extends AdminListHandler implements iAdminList
{
  public function __construct(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'attachment', 'template.xsl');
    parent::__construct($ctx);
  }

  protected function setUp($preset)
  {
    $this->types = array('file');
  }
}
