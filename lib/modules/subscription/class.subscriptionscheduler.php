<?php

class SubscriptionScheduler implements iScheduler
{
  /**
   * Рассылка новостей.
   */
  public static function taskRun(Context $ctx)
  {
    // Отправка почты занимает много времени.
    if (!ini_get('safe_mode'))
      set_time_limit(0);

    $types = mcms::modconf('subscription', 'types', array());

    if (empty($types))
      return;

    // Обрабатываем активных пользователей.
    foreach (Node::find(array('class' => 'subscription')) as $user) {
      $olast = $last = intval($user->last);

      // Получаем список разделов, на которые распространяется подписка.
      $tags = Node::find(array(
        'class' => 'tag',
        'published' => 1,
        'tagged' => $user->id,
        ));

      if (empty($tags))
        continue;

      $nodes = Node::find(array(
        'class' => $types,
        'tags' => array_keys($tags),
        'id' => array('>'. $last),
        '#sort' => 'id',
        ));

      // Отправляем документы.
      foreach ($nodes as $node) {
        $text = $node->text . t('<p><a href="@url">Отписаться от этих новостей</a></p>', array(
          '@url' => '?q=subscription.rpc&action=unsubscribe&email=' . urlencode($user->name)
            . '&id=' . $user->id,
          ));
        $mail = $user->name;

        BebopMimeMail::send(null, $mail, $node->name, $text);
        mcms::flog(sprintf("sent mail to %s: %s", $user->name, $node->name));
        $last = max($last, $node->id);
      }

      // Запоминаем последнее отправленное сообщение.
      $user->last = $last;
      $user->save();
    }
  }
}
