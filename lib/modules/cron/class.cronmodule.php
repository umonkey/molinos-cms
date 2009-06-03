<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CronModule
{
  public static function on_rpc(Context $ctx)
  {
    if (!empty($_SERVER['HTTP_HOST']) and !$ctx->canDebug())
      throw new BadRequestException(t('Запуск планировщика возможен только из консоли.'));

    @set_time_limit(0);

    header('HTTP/1.1 200 OK');
    header('Content-Type: text/plain; charset=utf-8');

    $ctx->registry->broadcast('ru.molinos.cms.cron', array($ctx));

    self::touch($ctx);

    if (null !== ($next = $ctx->get('destination')))
      $ctx->redirect($next);

    die("OK\n");
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
