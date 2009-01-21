<?php

class CronMenu implements iAdminMenu
{
  public static function getMenuIcons()
  {
    try {
      $node = Node::load(array('class' => 'cronstats'));

      if ((time() - strtotime($node->updated)) < 86400)
        return;
    } catch (ObjectNotFoundException $e) {
    }

    return array(
      array(
        'group' => 'status',
        'message' => t('Рекомендуется запустить планировщик заданий.'),
        'link' => '?q=admin&mode=modules&action=config&name=cron&cgroup=structure&destination=CURRENT',
        ),
      );
  }

  private static function getIcon($msg)
  {
    $message = t('Планировщик заданий %msg не запускался; '
      . 'обновление системы, рассылка новостей и другие вещи '
      . 'работать не будут. ', array('%msg' => $msg));

    if (CronModule::isClientAllowed())
      $message .= t('<a href=\'@url\'>Запустите его</a>.', array(
        '@url' => '?q=cron.rpc&destination=CURRENT',
        ));
    else
      $message .= t('Ваших полномочий недостаточно для ручного запуска '
        .'(см. <a href=\'@url\'>настройки модуля</a>).', array(
          '@url' => '?q=admin&mode=modules&action=config&name=cron&cgroup=structure&destination=CURRENT',
        ));

    $icon = array(
      'group' => 'status',
      'message' => '<p class="important">' . $message . '</p>',
      );

    return $icon;
  }
}
