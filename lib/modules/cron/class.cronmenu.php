<?php

class CronMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    try {
      $node = Node::load(array('class' => 'cronstats'));

      if ((time() - strtotime($node->updated)) > 86400)
        $icons[] = self::getIcon(t('давно'));
    } catch (ObjectNotFoundException $e) {
      $icons[] = self::getIcon(t('ни разу'));
    }

    return $icons;
  }

  private static function getIcon($msg)
  {
    $icon = array(
      'group' => 'status',
      'message' => t('Планировщик заданий %msg не запускался; '
        .'обновление системы, рассылка новостей и другие вещи '
        .'работать не будут. ', array(
          '%msg' => $msg,
          )),
      );

    if (CronModule::isClientAllowed())
      $icon['message'] .= t('<a href=\'@url\'>Запустите его</a>.', array(
        '@url' => '?q=cron.rpc&destination=CURRENT',
        ));
    else
      $icon['message'] .= t('Ваших полномочий недостаточно для ручного запуска '
        .'(см. <a href=\'@url\'>настройки модуля</a>).', array(
          '@url' => 'admin?mode=modules&action=config&name=cron&cgroup=structure&destination=CURRENT',
        ));

    return $icon;
  }
}
