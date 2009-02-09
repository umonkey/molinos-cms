<?php

class StatusChecker implements iScheduler
{
  public static function taskRun(Context $ctx)
  {
    $parts = array();

    NodeIndexer::run();

    if ($message = self::getBrokenTrees($ctx))
      $parts[] = $message;

    if ($idx = NodeIndexer::stats(false)) {
      $total = $idx['_total'];
      unset($idx['_total']);

      $list = array();

      foreach ($idx as $k => $v)
        $list[] = $k . ': ' . $v;

      $parts[] = t('Есть непроиндексированные объекты (!list); они будут выпадать из запросов, использующих поиск и сортировку по нестандартным полям.', array(
        '!list' => join(', ', $list),
        ));
    }

    if (null !== ($message = self::checkAccessRights($ctx)))
      $parts[] = $message;

    if (class_exists('UpdateMenu') and ($message = UpdateMenu::getMessage()))
      $parts[] = $message;

    if (null !== ($message = self::checkDbAccess($ctx)))
      $parts[] = $message;

    if (!empty($parts) and ($email = self::getEmail())) {
      $subject = t('Отчёт о состоянии %host', array(
        '%host' => url::host(),
        ));

      $body = t('<p>Анализ сайта %host выявил следующие аномалии:</p>!list<p>!signature</p>', array(
        '%host' => url::host(),
        '!list' => '<ol><li>' . join('</li><li>', $parts) . '</li></ol>',
        '!signature' => mcms::getSignature()
        ));

      if (!BebopMimeMail::send(null, $email, $subject, $body))
        throw new RuntimeException(t('Не удалось отправить почту администратору сайта.'));
    }
  }

  private static function getEmail()
  {
    if ($conf = mcms::modconf('admin', 'admin')) {
      try {
        $node = Node::load(array(
          'id' => $conf,
          'class' => 'user',
          ));

        return $node->getEmail();
      } catch (ObjectNotFoundException $e) {
      }
    }
  }

  private static function getSummary(Context $ctx, array &$parts)
  {
    $parts = array();

    self::count($ctx, $parts, 'SELECT COUNT(*) FROM `node`',
      'Объектов: !count', '?q=admin/content/list&columns=name,class,uid,created');

    self::count($ctx, $parts, 'SELECT COUNT(*) FROM `node` WHERE `deleted` = 1',
      'удалённых: !count', '?q=admin/content/list/trash');

    self::count($ctx, $parts, 'SELECT COUNT(*) FROM `node` WHERE `published` = 0 AND `deleted` = 0',
      'в модерации: !count', '?q=admin/content/list/drafts');

    self::count($ctx, $parts, 'SELECT COUNT(*) FROM `node__session`',
      'сессий: !count');

    if ('SQLite' == $ctx->db->getDbType())
      $parts[] = t('объём&nbsp;БД:&nbsp;%size', array(
        '%size' => mcms::filesize($ctx->db->getDbName()),
        ));

    if ($tmp = mcms::config('runtime.modules')) {
      $parts[] = t('<a href=\'@url\'>модулей</a>:&nbsp;%count', array(
        '%count' => count(explode(',', $tmp)),
        '@url' => '?q=admin/structure/modules',
        ));
    }

    return join(', ', $parts);
  }

  private static function getBrokenTrees(Context $ctx)
  {
    if (!($count = $ctx->db->fetch("SELECT COUNT(*) FROM `node` `n` WHERE `n`.`deleted` = 0 AND `n`.`left` >= `n`.`right`")))
      return null;

    $fixxxer = new TreeBuilder();
    $fixxxer->run();

    $result = t('Были найдены повреждённые ветки дерева (%count шт). Всё дерево данных было перестроено.', array(
      '%count' => $count,
      ));

    return $result;
  }

  private static function count(Context $ctx, array &$parts, $query, $text, $link = null)
  {
    if ($count = $ctx->db->fetch($query)) {
      if (null !== $link)
        $count = l($link, $count);

      $parts[] = t($text, array(
        '!count' => $count,
        ));
    }
  }

  private static function checkAccessRights(Context $ctx)
  {
    $types = $ctx->db->getResultsKV("id", "name", "SELECT n.id, v.name FROM node n WHERE n.class = 'type' AND n.deleted = 0 AND n.id IN (SELECT nid FROM node__access WHERE uid = 0 AND (u = 1 OR d = 1 OR p = 1))");

    if (!empty($types)) {
      $list = array();

      foreach ($types as $id => $name)
        $list[] = l('?q=admin/structure/edit/' . $id . '&destination=CURRENT', $name);

      return t('<p class="important">Нарушение безопасности: документы типов !list могут быть изменены анонимно.</p>', array(
        '!list' => join(', ', $list),
        ));
    }
  }

  private static function checkDbAccess(Context $ctx)
  {
    if (null !== ($file = $ctx->db->getDbFile())) {
      if (0 === strpos(realpath($file), MCMS_ROOT . DIRECTORY_SEPARATOR)) {
        $url = 'http://' . url::host() . mcms::path() . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $file);

        if (false !== ($headers = get_headers($url, 1))) {
          if (3 == count($parts = explode(' ', $headers[0]))) {
            if (200 == $parts[1]) {
              return t('Файл базы данных доступен веб серверу, любой желающий может <a href=\'@url\'>скачать его</a>. Пожалуйста, вынесите его в папку, недоступную веб серверу, и измените путь в конфигурационном файле (%config).', array(
                '@url' => $url,
                '%config' => mcms::config('fullpath'),
                ));
            }
          }
        }
      }
    }
  }
}
