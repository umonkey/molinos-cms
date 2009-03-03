<?php

class Query
{
  private $tables = array();
  private $conditions = array();
  private $params = array();
  private $order = array();

  public function __construct(array $filters)
  {
    $this->tables[] = 'node';

    $this->findSpecial($filters);
    $this->findBasics($filters);
  }

  private function findSpecial(array &$filters)
  {
    foreach ($filters as $k => $v) {
      if (0 === strpos($k, '#')) {
        unset($filters[$k]);

        switch ($k) {
        case '#sort':
          foreach (preg_split('/[, ]+/', $v, -1, PREG_SPLIT_NO_EMPTY) as $key) {
            list($fieldName, $neg) = $this->getFieldSpec($key);
            $this->order[] = $fieldName . ($neg ? ' DESC' : ' ASC');
          }
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
            $this->conditions[] = '(`node`.`data` LIKE ? OR `node`.`name` LIKE ?)';
            $this->params[] = $like;
            $this->params[] = $like;
          }

          break;

        case '#public':
          if (!empty($v))
            $this->conditions[] = '`node`.`class` IN (SELECT `name` FROM `node` WHERE `class` = \'type\' AND `published` = 1 AND `deleted` = 0)';
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
      if ('tags' == $k) {
        $this->conditions[] = "`node`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` " . $this->getTagsFilter($v) . ")";
        $this->params[] = intval($v);
      }

      else {
        list($fieldName, $neg) = $this->getFieldSpec($k);

        if ('tags' == $k)
          mcms::debug($k, $v, $fieldName, $neg);

        if ($neg)
          $this->conditions[] = $fieldName . " " . sql::notIn($v, $this->params);
        else
          $this->conditions[] = $fieldName . " " . sql::in($v, $this->params);
      }
    }
  }

  private function getTagsFilter($id)
  {
    if ('+' == substr($id, -1))
      $sql = "IN (SELECT `n`.`id` FROM `node` `n`, `node` `t` WHERE `n`.`class` = 'tag' AND `n`.`left` >= `t`.`left` AND `n`.`right` <= `t`.`right` AND `t`.`id` = ? AND `n`.`deleted` = 0 AND `n`.`published` = 1)";
    else
      $sql = "= ?";

    return $sql;
  }

  /**
   * Возвращает инструкцию для выборки идентификаторов.
   */
  public function getSelect($limit = null, $offset = null)
  {
    $sql = sql::getSelect(array('`node`.`id`'), $this->tables, $this->conditions);

    if (!empty($this->order))
      $sql .= ' ORDER BY ' . join(', ', $this->order);

    if (null !== $limit) {
      $lim = array();
      if (null !== $offset)
        $lim[] = intval($offset);
      $lim[] = intval($limit);
      $sql .= ' LIMIT ' . join(', ', $lim);
    }

    return array($sql, $this->params);
  }

  public function getCount()
  {
    $sql = sql::getSelect(array('COUNT(*)'), $this->tables, $this->conditions);

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

    if (NodeStub::isBasicField($name)) {
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
}
