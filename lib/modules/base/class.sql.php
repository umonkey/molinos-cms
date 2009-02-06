<?php

class sql
{
  public static function in($value, array &$params)
  {
    if (empty($value))
      return 'IS NULL';

    if (!is_array($value)) {
      $params[] = $value;
      return '= ?';
    }

    foreach ($value as $v)
      $params[] = $v;

    return 'IN (' . rtrim(str_repeat('?, ', count($value)), ', ') . ')';
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
    $sql = 'INSERT INTO `' . $tableName . '` (`' . join('`, `', array_keys($values)) . '`) VALUES (' . substr(str_repeat('?, ', count($values)), 0, -2) . ')';
    return array($sql, array_values($values));
  }
}
