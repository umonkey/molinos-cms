<?php

class SubscriptionScheduler
{
  /**
   * @mcms_message ru.molinos.cms.cron
   */
  public static function taskRun(Context $ctx)
  {
    // Отправка почты занимает много времени.
    if (!ini_get('safe_mode'))
      set_time_limit(0);

    $types = $ctx->modconf('subscription', 'types', array());
    $xsl = $ctx->modconf('subscription', 'stylesheet', os::path('lib', 'modules', 'subscription', 'message.xsl'));
    $sub = $ctx->modconf('subscription', 'subject', 'Новости сайта %host');

    if (empty($types))
      return;

    $ctx->db->beginTransaction();

    $users = Node::find($ctx->db, array(
      'class' => 'subscription',
      'deleted' => 0,
      'published' => 1,
      '#sort' => 'name',
      ));

    // Обрабатываем активных пользователей.
    foreach ($users as $user) {
      $olast = $last = intval($user->last);

      // Получаем список разделов, на которые распространяется подписка.
      $tags = Node::find($ctx->db, array(
        'class' => 'tag',
        'published' => 1,
        'tagged' => $user->id,
        ));

      if (empty($tags))
        continue;

      $nodes = self::getNodes($ctx, $types, $tags, $last);

      // Отправляем документы.
      foreach ($nodes as $node) {
        $xml = html::em('message', array(
          'mode' => 'regular',
          'unsubscribe' => '?q=subscription.rpc&action=remove&name=' . urlencode($user->name),
          'base' => $ctx->url()->getBase($ctx),
          'host' => url::host(),
          ), $node->getXML('document'));

        $body = xslt::transform($xml, $xsl, null);
        $subject = t($sub, array(
          '%host' => $ctx->url()->host(),
          ));

        BebopMimeMail::send(null, $user->name, $subject, $body);
        $last = max($last, $node->id);
      }

      // Запоминаем последнее отправленное сообщение.
      $user->last = $last;
      $user->save();
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

  private static function getNodes(Context $ctx, array $types, array $sections, $last)
  {
    $params = array(intval($last));

    $tags = array();
    foreach ($sections as $section)
      $tags[] = $section->id;

    $sql = 'SELECT `id` FROM `node` WHERE `deleted` = 0 AND `published` = 1 AND `id` > ? AND `class` ' . sql::in($types, $params);
    $sql .= ' AND `id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` ' . sql::in($tags, $params) . ')';
    $sql .= ' ORDER BY `created` DESC';

    $result = array();
    foreach ((array)$ctx->db->getResultsV('id', $sql, $params) as $id)
      $result[] = NodeStub::create($id, $ctx->db);

    return $result;
  }
}
