<?php

class SubscriptionScheduler implements iScheduler
{
  /**
   * Рассылка новостей.
   */
  public static function taskRun()
  {
    // Отправка почты занимает много времени.
    if (!ini_get('safe_mode'))
      set_time_limit(0);

    $types = mcms::modconf('subscription', 'types', array());

    if (empty($types)) {
      printf("  subscription: no types.\n");
      return;
    }

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

      // mcms::debug($user, $last, $nodes);

      // Отправляем документы.
      foreach ($nodes as $node) {
        BebopMimeMail::send(null, $user->name, $node->name, $node->text);
        printf("    sent mail to %s: %s\n", $user->name, $node->name);
        $last = max($last, $node->id);
      }

      // Запоминаем последнее отправленное сообщение.
      $user->last = $last;
      $user->save();
    }
  }
}
