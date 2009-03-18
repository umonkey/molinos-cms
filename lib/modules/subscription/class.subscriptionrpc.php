<?php

class SubscriptionRPC extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.subscription
   */
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  public static function rpc_post_subscribe(Context $ctx)
  {
    $data = $ctx->post;

    if (empty($data['sections']))
      throw new InvalidArgumentException("Не выбраны разделы для подписки.");

    if (false === strpos($data['email'], '@'))
      throw new InvalidArgumentException(t('Введённый email не похож на email.'));

    // В массиве могут быть и другие данные, поэтому мы
    // выбираем только то, что нам нужно завернуть.
    $bulk = array(
      'email' => $data['email'],
      'sections' => $data['sections'],
      );

    $link = new url(array(
      'args' => array(
        'q' => 'subscription.rpc',
        'action' => 'confirm',
        'code' => base64_encode(serialize($bulk)),
        ),
      ));

    $sections = Node::findXML($ctx->db, array(
      'class' => 'tag',
      'deleted' => 0,
      'published' => 1,
      'id' => $data['sections'],
      '#sort' => 'name',
      ), null, null, 'section');
    if (empty($sections))
      throw new InvalidArgumentException("Выбраны несуществующие разделы для подписки.");

    $xml = html::em('message', array(
      'mode' => 'confirm',
      'host' => url::host(),
      'email' => $data['email'],
      'base' => $ctx->url()->getBase($ctx),
      'confirmLink' => $link->string(),
      ), html::em('sections', $sections));

    $xsl = $ctx->modconf('subscription', 'stylesheet', os::path('lib', 'modules', 'subscription', 'message.xsl'));

    if (false === ($body = xslt::transform($xml, $xsl, null)))
      throw new RuntimeException(t('Возникла ошибка при форматировании почтового сообщения.'));

    $subject = t('Подписка на новости сайта %host', array(
      '%host' => url::host(),
      ));

    // mcms::debug($data['email'], $subject, $body);

    BebopMimeMail::send(null, $data['email'], $subject, $body);
  }

  public static function rpc_get_confirm(Context $ctx)
  {
    if (!is_array($data = unserialize(base64_decode($ctx->get('code')))))
      throw new BadRequestException();

    // Немного валидации, на т.к. будем в БД класть данные.
    foreach ($data['sections'] as $k => $v)
      if (!is_numeric($v))
        throw new BadRequestException();

    // Номер последней ноды нужен для того, чтобы не отправлять
    // новому подписчику уже существующие новости.
    $last = $ctx->db->getResult("SELECT MAX(`id`) FROM `node`");

    try {
      $node = Node::load(array(
        'class' => 'subscription',
        'name' => $data['email'],
        'deleted' => 0,
        ));

      $status = t('Параметры подписки успешно изменены.');
    } catch (ObjectNotFoundException $e) {
      $node = Node::create('subscription', array(
        'name' => $data['email'],
        'last' => $last,
        'published' => true,
        ));

      $status = t('Подписка активирована.');
    }

    $ctx->db->beginTransaction();

    if (!empty($data['sections'])) {
      foreach ($data['sections'] as $id)
        $node->linkTo($id);
      $node->save();
    } elseif (!empty($node->id)) {
      $node->delete();
      $status = t('Подписка удалена.');
    }

    $ctx->db->commit();

    return $ctx->getRedirect('?status=subscribed');
  }

  protected static function rpc_get_remove(Context $ctx)
  {
    $name = $ctx->get('name');

    try {
      $node = Node::load(array(
        'class' => 'subscription',
        'name' => $name,
        'deleted' => 0,
        'published' => 1,
        ));
      if (empty($node))
        throw new PageNotFoundException(t('Этот адрес не подписан.'));
      $ctx->db->beginTransaction();
      $node->delete();
      $ctx->db->commit();
    } catch (ObjectNotFoundException $e) {
    }

    return $ctx->getRedirect('?unsubscribed=' . urlencode($name));
  }
}
