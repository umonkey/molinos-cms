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

  public static function on_get_list(Context $ctx)
  {
    $class = __CLASS__;
    $tmp = new $class($ctx);
    return $tmp->getHTML($ctx->get('preset'));
  }

  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/imgtransform',
        'method' => 'on_get_list',
        'title' => t('Преобразования картинок'),
        ),
      );
  }
}
