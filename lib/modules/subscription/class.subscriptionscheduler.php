<?php

class SubscriptionScheduler
{
  /**
   * @mcms_message ru.molinos.cms.cron
   */
  public static function taskRun(Context $ctx)
  {
    $types = $ctx->config->get('modules/subscription/types', array());
    $xsl = $ctx->config->get('modules/subscription/stylesheet', os::path('lib', 'modules', 'subscription', 'message.xsl'));
    $sub = $ctx->config->get('modules/subscription/subject', 'Новости сайта %host');

    if (empty($types))
      return;

    $ctx->db->beginTransaction();

    $users = Node::find(array(
      'class' => 'subscription',
      'deleted' => 0,
      'published' => 1,
      '#sort' => 'name',
      ), $ctx->db);

    // Обрабатываем активных пользователей.
    foreach ($users as $user) {
      $olast = $last = intval($user->last);

      if ($sections = (array)$user->tags) {
        list($sql, $params) = Query::build(array(
          'class' => $types,
          'tags' => $sections,
          'published' => 1,
          'deleted' => 0,
          'id' => array('>' . ($olast + 1)),
          ))->getSelect(array('id', 'xml'));
        $nodes = $ctx->db->getResultsKV('id', 'xml', $sql, $params);

        // Отправляем документы.
        foreach ($nodes as $nid => $node) {
          $xml = html::em('message', array(
            'mode' => 'regular',
            'unsubscribe' => 'subscription.rpc?action=remove&name=' . urlencode($user->name)
              . '&id=' . $user->id,
            'base' => $ctx->url()->getBase($ctx),
            'host' => MCMS_HOST_NAME,
            ), $node);

          $body = xslt::transform($xml, $xsl, null);
          $subject = t($sub, array(
            '%host' => $ctx->url()->host(),
            ));

          BebopMimeMail::send(null, $user->name, $subject, $body);
          $last = max($last, $nid);
        }

        // Запоминаем последнее отправленное сообщение.
        $user->last = $last;
        $user->save();
      }
    }

    $ctx->db->commit();
  }

  private static function getTagIds(array $tags)
  {
    $result = array();
    foreach ($tags as $tag)
      $result[] = $tag->id;
    return $result;
  }
}
