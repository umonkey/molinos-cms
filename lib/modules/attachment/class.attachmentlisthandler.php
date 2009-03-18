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
    $this->actions = array('delete');
    $this->nopermcheck = true;
  }

  /**
   * Рендерит список подписавшихся пользователей.
   * 
   * @param Context $ctx 
   * @return string
   * @mcms_message ru.molinos.cms.admin.list.attachment
   */
  public static function on_get_list(Context $ctx)
  {
    $class = __CLASS__;
    $tmp = new $class($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }
}
