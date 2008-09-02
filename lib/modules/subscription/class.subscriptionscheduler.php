<?php

class SubscriptionScheduler implements iScheduler
{
  // Выполнение периодических задач.
  public static function taskRun()
  {
    $pdo = mcms::db();

    // Отправка почты занимает много времени.
    if (!ini_get('safe_mode'))
      set_time_limit(0);

    $types = array();

    foreach (Node::find(array('class' => 'type', 'sendmail' => 1)) as $type)
      $types[] = $type->name;

    if (empty($types)) {
      printf("  subscription: no types.\n");
      return;
    }

    // Обрабатываем активных пользователей.
    foreach (Node::find(array('class' => 'subscription')) as $user) {
      $last = null;

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
        'id' => ('>'. $user->last),
        '#sort' => 'id',
        ));

      // Отправляем документы.
      foreach ($nodes as $node) {
        BebopMimeMail::send(null, $user->name, $node->name, $node->text);
        printf("    sent mail to %s: %s\n", $user->name, $node->name);
        $last = max($last, $node->id);
      }

      // Запоминаем последнее отправленное сообщение.
      if ($last !== null) {
        $user->last = $last;
        $user->save();
      }
    }
  }
}
