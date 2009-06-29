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
    $key = 'acl:type:' . implode(',', $groups);
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
}
