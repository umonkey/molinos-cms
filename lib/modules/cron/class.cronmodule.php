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

  public static function hookRemoteCall(Context $ctx, $className)
  {
    if (!empty($_SERVER['HTTP_HOST']))
      throw new BadRequestException(t('Запуск планировщика возможен только из консоли.'));

    if (!self::isClientAllowed())
      throw new ForbiddenException(t('Настройки модуля cron не позволяют вам '
        .'запускать периодические задачи.'));

    @set_time_limit(0);

    header('HTTP/1.1 200 OK');
    header('Content-Type: text/plain; charset=utf-8');

    $args = array($ctx);
    mcms::invoke('iScheduler', 'taskRun', $args);

    self::touch($ctx);

    if (null !== ($next = $ctx->get('destination')))
      $ctx->redirect($next);

    die("OK\n");
  }

  public static function isClientAllowed()
  {
    // Запуск из консоли разрешён всегда.
    if (empty($_SERVER['REMOTE_ADDR']))
      return true;

    if ('127.0.0.1' == $_SERVER['REMOTE_ADDR'])
      return true;

    if (!($ips = mcms::modconf('cron', 'allowed')))
      return true;

    return mcms::matchip($_SERVER['REMOTE_ADDR'], $ips);
  }

  private static function touch(Context $ctx)
  {
    $node = Node::find(array('class' => 'cronstats'));
    if (empty($node))
      $node = Node::create('cronstats');
    else
      $node = array_shift($node);
    $ctx->db->beginTransaction();
    $node->save();
    $ctx->db->commit();
  }
};
