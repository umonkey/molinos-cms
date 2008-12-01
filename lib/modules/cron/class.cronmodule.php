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

  public static function hookRemoteCall(Context $ctx)
  {
    if (!self::isClientAllowed())
      throw new ForbiddenException(t('Настройки модуля cron не позволяют вам '
        .'запускать периодические задачи.'));

    set_time_limit(0);

    header('HTTP/1.1 200 OK');
    header('Content-Type: text/plain; charset=utf-8');

    mcms::invoke('iScheduler', 'taskRun');

    self::touch();

    if (null !== ($next = $ctx->get('destination')))
      $ctx->redirect($next);

    die("OK\n");
  }

  public static function isClientAllowed()
  {
    // Запуск из консоли разрешён всегда.
    if (empty($_SERVER['REMOTE_ADDR']))
      return true;

    return mcms::matchip($_SERVER['REMOTE_ADDR'],
      mcms::modconf('cron', 'allowed', '127.0.0.1'));
  }

  private static function touch()
  {
    try {
      $node = Node::load(array('class' => 'cronstats'));
    } catch (ObjectNotFoundException $e) {
      $node = Node::create('cronstats');
    }

    $node->save();
  }
};
