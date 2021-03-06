<?php

class Query
{
  private $tables = array();
  private $conditions = array();
  private $params = array();
  private $order = array();
  private $limit = null;
  private $offset = null;
  private $debug = false;

  /**
   * То же самое, что new, только возвращает объект.
   * В основном предназначен для организации цепочек.
   */
  public static function build(array $filters)
  {
    $q = new Query($filters);
    return $q;
  }

  public function __construct(array $filters)
  {
    $this->tables[] = 'node';

    $this->findSpecial($filters);
    $this->findBasics($filters);
  }

  private function findSpecial(array &$filters)
  {
    foreach ($filters as $k => $v) {
      // Поиск с помощью LIKE
      if ('?|' == substr($k, -2)) {
        unset($filters[$k]);

        list($fieldName, $neg) = $this->getFieldSpec(substr($k, 0, -2));

        $parts = array();
        foreach ((array)$v as $part) {
          $parts[] = $fieldName . ' LIKE ?';
          $this->params[] = $part;
        }

        $sql = implode(' OR ', $parts);
        if (count($parts) > 1)
          $sql = '(' . $sql . ')';

        if (!empty($sql))
          $this->conditions[] = $sql;
      }

      elseif (0 === strpos($k, '#')) {
        unset($filters[$k]);

        switch ($k) {
        case '#sort':
          foreach (preg_split('/[, ]+/', $v, -1, PREG_SPLIT_NO_EMPTY) as $key) {
            if ('name' === $key)
              $key = 'name_lc';
            list($fieldName, $neg) = $this->getFieldSpec($key);
            $this->order[] = $fieldName . ($neg ? ' DESC' : ' ASC');
          }
          break;

        case '#debug':
          $this->debug = !empty($v);
          break;

        case '#search':
          $keywords = preg_split('/ +/', $v, -1, PREG_SPLIT_NO_EMPTY);

          foreach ($keywords as $idx => $kw) {
            if (count($parts = explode(':', $kw, 2)) == 2) {
              $filters[$parts[0]] = $parts[1];
              unset($keywords[$idx]);
            }
          }

          // Если что-то осталось — используем обычный поиск.
          if (!empty($keywords)) {
            $like = '%' . join('%', $keywords) . '%';
            $this->conditions[] = '(`node`.`data` LIKE ? OR `node`.`name_lc` LIKE ?)';
            $this->params[] = $like;
            $this->params[] = mb_strtolower($like);
          }

          break;

        case '#public':
          if (!empty($v))
            $this->conditions[] = '`node`.`class` IN (SELECT `name` FROM `node` WHERE `class` = \'type\' AND `published` = 1 AND `deleted` = 0)';
          break;

        case '#limit':
          $this->limit = intval($v);
          break;

        case '#offset':
          $this->offset = intval($v);
          break;

        default:
          throw new InvalidArgumentException(t('Неизвестный фильтр в запросе: %name.', array(
            '%name' => $k,
            )));
        }
      }
    }
  }

  private function findBasics(array &$filters)
  {
    foreach ($filters as $k => $v) {
      switch ($k) {
      case 'tags':
        $this->conditions[] = "(`node`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` " . $this->getTagsFilter($v) . "))";
        break;
      case 'tagged':
        $this->conditions[] = "`node`.`id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` " . $this->getTagsFilter($v) . ")";
        break;
      default:
        list($fieldName, $neg) = $this->getFieldSpec($k);

        if ('`node`.`name_lc`' == $fieldName) {
          $tmpv = array();
          foreach ((array)$v as $v1)
            $tmpv[] = self::getSortName($v1);
          $v = $tmpv;
        }

        if ($neg)
          $this->conditions[] = $fieldName . " " . sql::notIn($v, $this->params);
        else
          $this->conditions[] = $fieldName . " " . sql::in($v, $this->params, sql::range);
        break;
      }
    }
  }

  private function getTagsFilter($id)
  {
    if (is_string($id) and '+' == substr($id, -1)) {
      $sql = "IN (SELECT `n`.`id` FROM `node` `n`, `node` `t` WHERE `n`.`class` = 'tag' AND `n`.`left` >= `t`.`left` AND `n`.`right` <= `t`.`right` AND `t`.`id` = ? AND `n`.`deleted` = 0 AND `n`.`published` = 1)";
      $this->params[] = intval($id);
    } else {
      $sql = sql::in($id, $this->params);
    }

    return $sql;
  }

  /**
   * Возвращает инструкцию для выборки идентификаторов.
   */
  public function getSelect(array $fields = null)
  {
    if (!$fields)
      $fields = array('`node`.`id`', '`parent_id`', '`name`', '`lang`', '`class`', '`left`', '`right`', '`created`', '`updated`', '`published`', '`uid`', '`deleted`', '`data`');

    $sql = sql::getSelect((array)$fields, $this->tables, $this->conditions);

    if (!empty($this->order))
      $sql .= ' ORDER BY ' . join(', ', $this->order);

    if (null !== $this->limit) {
      $lim = array();
      if (null !== $this->offset)
        $lim[] = intval($this->offset);
      $lim[] = intval($this->limit);
      $sql .= ' LIMIT ' . join(', ', $lim);
    }

    if ($this->debug)
      mcms::debug($sql, $this->params);

    return array($sql, $this->params);
  }

  public function getCount(Database $db = null)
  {
    $sql = sql::getSelect(array('COUNT(*)'), $this->tables, $this->conditions);

    if (null !== $db)
      return $db->fetch($sql, $this->params);

    return array($sql, $this->params);
  }

  private function getFieldSpec($name)
  {
    if (false !== strpos($name, '.'))
      throw new InvalidArgumentException(t('Непонятно указано поле: %field.', array(
        '%field' => $name,
        )));

    if ($neg = ('-' == substr($name, 0, 1)))
      $name = substr($name, 1);

    if ('name' == $name) {
      $tableName = 'node';
      $fieldName = 'name_lc';
    } elseif (Node::isBasicField($name)) {
      $tableName = 'node';
      $fieldName = $name;
    } else {
      $tableName = 'node__idx_' . $name;
      $fieldName = 'value';
    }

    if (!in_array($tableName, $this->tables)) {
      $this->tables[] = $tableName;
      if ('node' != $tableName)
        $this->conditions[] = "`node`.`id` = `{$tableName}`.`id`";
    }

    return array("`{$tableName}`.`{$fieldName}`", $neg);
  }

  public static function getSortName($name)
  {
    $sortName = mb_strtolower($name);
    if (substr($sortName, 0, 4) == 'the ')
      $sortName = ltrim(substr($sortName, 4)) . ', the';
    elseif (substr($sortName, 0, 2) == 'a ')
      $sortName = ltrim(substr($sortName, 2)) . ', a';
    $sortName = preg_replace('/[\[\]]/', '', $sortName);
    return $sortName;
  }
}
