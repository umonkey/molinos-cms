<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MaintenanceModule
{
  private static function isLocked(Context $ctx, $enable_admin = true)
  {
    $conf = $ctx->config->get('modules/maintenance');

    if (!empty($conf['state']) and 'closed' === $conf['state']) {
      if (!$enable_admin)
        return true;
      if (!$ctx->canDebug()) {
      $query = $ctx->query();
      if ('admin' != $query and 0 !== strpos($query, 'admin/'))
        return true;
      }
    }

    return false;
  }

  /**
   * @mcms_message ru.molinos.cms.page.content
   */
  public static function hookRequest(Context $ctx)
  {
    if (true === self::isLocked($ctx)) {
      $r = new Response(t('На сервере ведутся технические работы, обратитесь чуть позже.'), 'text/plain', 503);
      $r->send();
    }
  }

  /**
   * @mcms_message ru.molinos.cms.admin.status.enum
   */
  public static function get_notifications(Context $ctx, array &$messages)
  {
    if (self::isLocked($ctx, false))
      $messages[] = array(
        'message' => t('Доступ к сайту закрыт: ведутся технические работы'),
        'link' => '?q=admin&action=form&module=modman&mode=config&name=maintenance&cgroup=system&destination=CURRENT',
        );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.maintenance
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'state' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Текущее состояние'),
        'options' => array(
          '' => t('Сайт работает'),
          'closed' => t('Ведутся технические работы'),
          ),
        ),
      ));
  }
}
