<?php

class SubscriptionRPC implements iRemoteCall
{
  public static function hookRemoteCall(Context $ctx)
  {
    $status = null;
    $next = $ctx->get('destination', '');

    switch ($ctx->get('action')) {
    case 'subscribe':
      $data = $ctx->post;

      if (empty($data['sections']))
        throw new InvalidArgumentException("Не выбраны разделы для подписки.");

      if (false === strpos($data['email'], '@'))
        throw new InvalidArgumentException(t('Email не похож на email.'));

      // В массиве могут быть и другие данные, поэтому мы
      // выбираем только то, что нам нужно завернуть.
      $bulk = array(
        'email' => $data['email'],
        'sections' => $data['sections'],
        );

      $catlist = '';

      foreach (Node::find(array('class' => 'tag', 'id' => $data['sections'], '#sort' => array('name' => 'asc'))) as $tmp)
        $catlist .= '<li>'. mcms_plain($tmp->name) .'</li>';

      $link = new url(array(
        'args' => array(
          'q' => 'subscription.rpc',
          'action' => 'confirm',
          'code' => base64_encode(serialize($bulk)),
          ),
        ));

      // Формируем текст почтового сообщения.
      if (count($catlist) > 1)
        $body = t("<p>Здравствуйте! Я — почтовый робот сайта %host, и я хотел бы уточнить, действительно ли "
          ."Вы хотите подписаться на новости нашего сайта в следующих категориях:</p><ol>%list</ol>"
          ."<p>Чтобы активировать подписку, пройдите, пожалуйста, по <a href='@link'>этой ссылке</a>.&nbsp; "
          ."Вы можете проигнорировать это сообщение, тогда подписка на новости изменена не будет.</p>", array(
          '%host' => $_SERVER['HTTP_HOST'],
          '%list' => $catlist,
          '@link' => $link->string(),
          ));
      else
        $body = t("<p>Здравствуйте! Я — почтовый робот сайта %host, и я хотел бы уточнить, действительно ли "
          ."Вы хотите подписаться на новости нашего сайта.</p>"
          ."<p>Чтобы активировать подписку, пройдите, пожалуйста, по <a href='@link'>этой ссылке</a>.&nbsp; "
          ."Вы можете проигнорировать это сообщение, тогда подписка на новости изменена не будет.</p>", array(
          '%host' => $_SERVER['HTTP_HOST'],
          '@link' => $link->string(),
          ));

      BebopMimeMail::send(null, $data['email'], t('Подписка на новости сайта %host', array('%host' => $_SERVER['HTTP_HOST'])), $body);
      $status = t('Инструкция отправлена по почте.');
      break;

    case 'confirm':
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
          ));

        $status = t('Параметры подписки успешно изменены.');
      } catch (ObjectNotFoundException $e) {
        $node = Node::create('subscription', array(
          'name' => $data['email'],
          'last' => $last,
          ));

        $status = t('Подписка активирована.');
      }

      if (!empty($data['sections'])) {
        $node->save();
        $node->linkSetParents(array_keys($data['sections']), 'tag');
      } elseif (!empty($node->id)) {
        $node->delete();
        $status = t('Подписка удалена.');
      }

      bebop_on_json(array(
        'status' => 'ok',
        'message' => $status,
        ));

      break;
    }

    $next = new url($next);
    $next->setarg('message', $status);
    $ctx->redirect($next);
  }
}
