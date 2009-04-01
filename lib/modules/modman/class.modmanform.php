<?php

class ModmanForm
{
  /**
   * @mcms_message ru.molinos.cms.admin.form.modman
   */
  public static function getAdminFormXML(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'modman', 'template.xsl');

    switch ($ctx->get('mode')) {
    case 'addremove':
      return self::getInstallForm($ctx);
    case 'config':
      return self::getConfigForm($ctx);
    case 'upgrade':
      return self::getUpgradeForm($ctx);
    default:
      return self::getSettingsForm($ctx);
    }
  }

  protected static function getUpgradeForm(Context $ctx)
  {
    if (count($modules = modman::getUpdatedModules())) {
      return self::getXML($ctx, $modules, array(
        'title' => t('Обновление модулей'),
        ));
    }
  }
}
