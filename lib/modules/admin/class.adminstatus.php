<?php
/**
 * Статистика базы данных.
 *
 * @package mod_admin
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Провайдер статистики БД.
 *
 * Выводит статистику БД на главной странице административного интерфейса.
 *
 * @package mod_admin
 * @subpackage Core
 */
class AdminStatus implements iAdminMenu
{
  public static function getMenuIcons()
  {
    $icons = array();

    if ('admin' != $_GET['q'])
      return $icons;

    if (!($p = mcms::user()->password) or $p == md5(''))
      if (0 !== strpos(mcms::user()->name, 'http://'))
        $icons[] = array(
          'group' => 'status',
          'message' => t('Пожалуйста, <a href=\'@url\'>установите пароль</a> '
            .'на ваш аккаунт.', array(
              '@url' => '?q=admin/access/edit&id=8&destination=CURRENT',
              )),
          );

    if (null !== ($stat = NodeIndexer::stats()))
      $icons[] = array(
        'group' => 'status',
        'message' => t('<p class="important">!count объектов нуждаются в индексации.  '
          .'Они будут проиндексирвоаны при выполнении планировщика, '
          .'или вы можете <a href=\'@url\'>проиндексировать их вручную</a>.  '
          .'Пока индексация не будет завершена, сортировка и выборка '
          .'будут работать некорректно.</p>', array(
            '!count' => $stat['_total'],
            '@url' => '?q=admin.rpc&action=reindex',
            )),
        );

    if (null !== ($message = self::checkAccessRights()))
      $icons[] = array(
        'group' => 'status',
        'message' => $message,
        );

    if (null !== ($counts = self::getCounts()))
      $icons[] = array(
        'group' => 'status',
        'message' => $counts,
        );

    return $icons;
  }

  private static function getCounts()
  {
    static $parts = null;

    if (null === $parts) {
      $parts = array();

      mcms::db()->log('-- status counter --');

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
    }

    return empty($parts)
      ? null
      : join(', ', $parts) .'.';
  }

  private static function count(array &$parts, $query, $text, $link = null)
  {
    if ($count = mcms::db()->fetch($query)) {
      if (null !== $link)
        $count = l($link, $count);

      $parts[] = t(str_replace(' ', '&nbsp;', $text), array(
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
