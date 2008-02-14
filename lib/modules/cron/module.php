<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CronModule implements iModuleConfig, iRemoteCall
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new TextLineControl(array(
      'value' => 'config_allowed',
      'label' => t('Разрешённые IP адреса'),
      'description' => t('Введите список IP-адресов, с которых разрешён запуск задач.  Запуск с локального сервера всегда разрешён.'),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookRemoteCall(RequestContext $ctx)
  {
    if (!self::isClientAllowed())
      throw new ForbiddenException(t('Вам не позволено запускать периодические задачи.'));

    header('HTTP/1.1 200 OK');
    header('Content-Type: text/plain; charset=utf-8');

    mcms::invoke('iScheduler', 'taskRun');

    die("OK\n");
  }

  private static function isClientAllowed()
  {
    if ('127.0.0.1' == $_SERVER['REMOTE_ADDR'])
      return true;

    if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR'])
      return true;

    $conf = mcms::modconf('cron');

    if (isset($conf['allowed']) and in_array($_SERVER['REMOTE_ADDR'], preg_split('/, */', $conf['allowed'])))
      return true;

    return false;
  }
};
