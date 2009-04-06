<?php

class sql
{
  public static function in($value, array &$params)
  {
    if (null === $value or array() === $value)
      return 'IS NULL';

    if (false === $value)
      $value = 0;
    elseif (true === $value)
      $value = 1;

    $value = array_unique((array)$value);

    if (1 == count($value)) {
      $params[] = array_shift($value);
      return '= ?';
    }

    foreach ($value as $v)
      $params[] = $v;

    return 'IN (' . rtrim(str_repeat('?, ', count($value)), ', ') . ')';
  }

  public static function notIn($value, array &$params)
  {
    if (empty($value))
      return 'IS NOT NULL';

    $value = array_unique((array)$value);

    if (1 == count($value)) {
      $params[] = array_shift($value);
      return '<> ?';
    }

    foreach ($value as $v)
      $params[] = $v;

    return 'NOT IN (' . rtrim(str_repeat('?, ', count($value)), ', ') . ')';
  }

  public static function getUpdate($tableName, array $values, $keyName)
  {
    if (!array_key_exists($keyName, $values))
      throw new InvalidArgumentException(t('Ключ не найден в полученном массиве.'));

    $key = $values[$keyName];
    unset($values[$keyName]);

    if (empty($key))
      throw new InvalidArgumentException(t('Обновление таблицы по пустому ключу невозможно.'));

    $sql = 'UPDATE `' . $tableName . '` SET `' . join('` = ?, `', array_keys($values)) . '` = ? WHERE `' . $keyName . '` = ?';
    $values[] = $key;

    return array($sql, array_values($values));
  }

  public static function getInsert($tableName, array $values)
  {
    foreach ($values as $k => $v)
      if (false === $v)
        $values[$k] = 0;
      elseif (true === $v)
        $values[$k] = 1;

    $sql = 'INSERT INTO `' . $tableName . '` (`' . join('`, `', array_keys($values)) . '`) VALUES (' . substr(str_repeat('?, ', count($values)), 0, -2) . ')';
    return array($sql, array_values($values));
  }

  public static function getDelete($tableName, array $conditions)
  {
    $where = $params = array();
    foreach ($conditions as $k => $v) {
      $where[] = '`' . $k . '` = ?';
      $params[] = $v;
    }

    $sql = 'DELETE FROM `' . $tableName . '` WHERE ' . join(' AND ', $where);
    return array($sql, $params);
  }

  public static function getSelect(array $fieldNames, array $tableNames, array $conditions)
  {
    $sql = "SELECT " . join(', ', $fieldNames)
      . " FROM `" . join('`, `', $tableNames) . "`";

    if (!empty($conditions))
      $sql .= ' WHERE ' . join(' AND ', $conditions);

    return $sql;
  }
}
