<?php

class ACL
{
  const CREATE = 1;
  const READ = 2;
  const UPDATE = 4;
  const DELETE = 8;
  const PUBLISH = 16;
  const OWN = 32;

  /**
   * Возвращает права на типы документов для пользователя из указанных групп.
   */
  public static function getTypeAccess(array $groups)
  {
    $key = 'acl:type:' . self::getSerial() . ':' . implode(',', $groups);
    $cache = Cache::getInstance();

    if (!is_array($result = $cache->$key)) {
      $params = $result = array();
      $data = Context::last()->db->getResults($sql = "SELECT `n`.`name` AS `type`, `o` AS `own`, "
        . "MAX(`c`) * 1 + MAX(`r`) * 2 + MAX(`u`) * 4 + MAX(`d`) * 8 + MAX(`p`) * 16 AS `sum` "
        . "FROM `node__access` `a` INNER JOIN `node` `n` ON `n`.`id` = `a`.`nid` "
        . "WHERE `a`.`uid` " . sql::in($groups, $params) . " AND `n`.`class` = 'type' AND `n`.`deleted` = 0 "
        . "GROUP BY `nid`, `o` ORDER BY `n`.`name`, `o`", $params);

      foreach ($data as $row)
        $result[$row['type']][$row['own']] = $row['sum'];

      $cache->$key = $result;
    }

    return $result;
  }

  /**
   * Изменяет права для определённого объекта.
   */
  public static function set($nid, $uid, $mode)
  {
    if (!intval($nid))
      throw new InvalidArgumentException(t('Идентификатор ноды должен быть числовым.'));
    elseif (!intval($mode))
      throw new InvalidArgumentException(t('Режим доступа должен быть числовым.'));

    $db = Context::last()->db;
    $db->exec("DELETE FROM `node__access` WHERE `nid` = ? AND `uid` = ? AND `o` = ?", array($nid, $uid, $mode & self::OWN ? 1 : 0));
    $db->exec("INSERT INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`, `o`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", array(
      intval($nid),
      intval($uid),
      $mode & self::CREATE ? 1 : 0,
      $mode & self::READ ? 1 : 0,
      $mode & self::UPDATE ? 1 : 0,
      $mode & self::DELETE ? 1 : 0,
      $mode & self::PUBLISH ? 1 : 0,
      $mode & self::OWN ? 1 : 0,
      ));

    self::flush();
  }

  /**
   * Копирует права одного объекта на другой.
   */
  public static function copyNode($fromId, $toId)
  {
    $db = Context::last()->db;
    $db->exec("DELETE FROM `node__access` WHERE `nid` = ?", array($toId));
    $db->exec("INSERT INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`, `o`) SELECT ?, `uid`, `c`, `r`, `u`, `d`, `p`, `o` FROM `node__access` WHERE `nid` = ?", array($toId, $fromId));
    self::flush();
  }

  /**
   * Возвращает идентификаторы нод, к которым есть нужный доступ.
   */
  public static function getPermittedNodeIds($mode, $nodeType = null)
  {
    $conditions = array();
    if ($mode & self::CREATE)
      $conditions[] = '`c` = 1';
    if ($mode & self::READ)
      $conditions[] = '`r` = 1';
    if ($mode & self::UPDATE)
      $conditions[] = '`u` = 1';
    if ($mode & self::DELETE)
      $conditions[] = '`d` = 1';
    if ($mode & self::PUBLISH)
      $conditions[] = '`p` = 1';
    if ($mode & self::OWN)
      $conditions[] = '`o` = 1';

    $params = array();
    $sql = 'SELECT `nid` FROM `node__access` WHERE (' . implode(' OR ', $conditions) . ') '
      . 'AND `uid` ' . sql::in(Context::last()->user->getGroups(), $params);

    if (null !== $nodeType) {
      $sql .= ' AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = ?)';
      $params[] = $nodeType;
    }

    return Context::last()->db->getResultsV('nid', $sql, $params);
  }

  /**
   * Преобразует массив crudp в число.
   */
  public static function asint(array $modes)
  {
    $result = 0;
    if (!empty($modes['c']))
      $result |= self::CREATE;
    if (!empty($modes['r']))
      $result |= self::READ;
    if (!empty($modes['u']))
      $result |= self::UPDATE;
    if (!empty($modes['d']))
      $result |= self::DELETE;
    if (!empty($modes['p']))
      $result |= self::PUBLISH;
    if (!empty($modes['o']))
      $result |= self::OWN;
    return $result;
  }

  /**
   * Возвращает итерацию кэша.
   */
  private static function getSerial()
  {
    return intval(Cache::getInstance()->acl_serial);
  }

  /**
   * Сбрасывает кэш.
   */
  private static function flush()
  {
    Cache::getInstance()->acl_serial = self::getSerial() + 1;
  }

  /**
   * Удаление прав при удалении объектов.
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function on_node_hook(Context $ctx, Node $node, $op)
  {
    if ('erase' == $op) {
      $node->getDB()->exec("DELETE FROM `node__access` WHERE `nid` = ? OR `uid` = ?", array($node->id, $node->id));
      self::flush();
    }
  }

  /**
   * Клонирование прав при клонировании объекта.
   * @mcms_message ru.molinos.cms.node.clone
   */
  public static function on_clone(Node $node)
  {
    $node->onSave("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`, `p`)"
      ."SELECT %ID%, `uid`, `c`, `r`, `u`, `d`, `p` FROM `node__access` WHERE `nid` = ?", array($node->id));
  }

  /**
   * Корректирует права (наследство старых версий).
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    $ctx->db->beginTransaction();
    foreach (array('c', 'r', 'u', 'd', 'p') as $key)
      $ctx->db->exec("UPDATE `node__access` SET `{$key}` = 0 WHERE `{$key}` <> 0 AND `{$key}` <> 1");
    $ctx->db->commit();
  }
}
