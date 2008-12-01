<?php

class StatusChecker implements iScheduler
{
  public static function taskRun()
  {
    $parts = array();

    NodeIndexer::run();

    if ($message = self::getBrokenTrees())
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

    if (null !== ($message = self::checkAccessRights()))
      $parts[] = $message;

    if (class_exists('UpdateMenu') and ($message = UpdateMenu::getMessage()))
      $parts[] = $message;

    if ($email = self::getEmail()) {
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

    mcms::debug($conf);
  }

  private static function getSummary(array &$parts)
  {
    $parts = array();

    self::count($parts, 'SELECT COUNT(*) FROM `node`',
      'Объектов: !count', '?q=admin/content/list&columns=name,class,uid,created');

    self::count($parts, 'SELECT COUNT(*) FROM `node` WHERE `deleted` = 1',
      'удалённых: !count', '?q=admin/content/list/trash');

    self::count($parts, 'SELECT COUNT(*) FROM `node` WHERE `published` = 0 AND `deleted` = 0',
      'в модерации: !count', '?q=admin/content/list/drafts');

    self::count($parts, 'SELECT COUNT(*) FROM `node__rev`',
      'ревизий: !count');

    self::count($parts, 'SELECT COUNT(*) FROM `node__rev` WHERE `rid` NOT IN (SELECT `rid` FROM `node`)', 
      'архивных: !count');

    self::count($parts, 'SELECT COUNT(*) FROM `node__session`',
      'сессий: !count');

    if ('SQLite' == mcms::db()->getDbType())
      $parts[] = t('объём&nbsp;БД:&nbsp;%size', array(
        '%size' => mcms::filesize(mcms::db()->getDbName()),
        ));

    if ($tmp = mcms::config('runtime.modules')) {
      $parts[] = t('<a href=\'@url\'>модулей</a>:&nbsp;%count', array(
        '%count' => count(explode(',', $tmp)),
        '@url' => '?q=admin/structure/modules',
        ));
    }

    return join(', ', $parts);
  }

  private static function getBrokenTrees()
  {
    $data = mcms::db()->getResults("SELECT `n`.`id`, `n`.`class`, `v`.`name` FROM `node` `n` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` WHERE `n`.`deleted` = 0 AND `n`.`left` >= `n`.`right`");

    if (empty($data))
      return null;

    $result = t('Есть повреждённые ветки дерева (%count шт), добавление дочерних объектов в них невозможно:', array(
      '%count' => count($data),
      ));

    $result .= '<table cellspacing=\'0\' cellpadding=\'4\'>';

    foreach ($data as $row) {
      $result .= '<tr>';
      $result .= mcms::html('td', $row['id'] . '&nbsp;');
      $result .= mcms::html('td', $row['class'] . '&nbsp;');
      $result .= mcms::html('td', mcms_plain($row['name']));
      $result .= '</tr>';
    }

    $result .= '</table>';

    return $result;
  }

  private static function count(array &$parts, $query, $text, $link = null)
  {
    if ($count = mcms::db()->fetch($query)) {
      if (null !== $link)
        $count = l($link, $count);

      $parts[] = t($text, array(
        '!count' => $count,
        ));
    }
  }

  private static function checkAccessRights()
  {
    $types = mcms::db()->getResultsKV("id", "name", "SELECT n.id, v.name FROM node n INNER JOIN node__rev v ON v.rid = n.rid WHERE n.class = 'type' AND n.deleted = 0 AND n.id IN (SELECT nid FROM node__access WHERE uid = 0 AND (u = 1 OR d = 1 OR p = 1))");

    if (!empty($types)) {
      $list = array();

      foreach ($types as $id => $name)
        $list[] = l('?q=admin/structure/edit/' . $id . '&destination=CURRENT', $name);

      return t('<p class="important">Нарушение безопасности: документы типов !list могут быть изменены анонимно.</p>', array(
        '!list' => join(', ', $list),
        ));
    }
  }
}
