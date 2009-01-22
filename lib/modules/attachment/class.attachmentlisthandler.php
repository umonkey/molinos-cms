<?php

class AttachmentListHandler extends AdminListHandler implements iAdminList
{
  public function __construct(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'attachment', 'template.xsl');
    parent::__construct($ctx);
  }

  protected function setUp($preset = null)
  {
    $this->types = array('imgtransform');
    $this->title = t('Правила трансформации картинок');
    $this->actions = array('clone', 'delete');
    $this->nopermcheck = true;
  }
}
