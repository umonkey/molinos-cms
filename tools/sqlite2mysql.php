<?php

function get_pdo($fileName)
{
  return new PDO('sqlite:' . $fileName);
}

function get_file($fileName)
{
  $f = fopen($fileName, 'w')
    or die($fileName . ": could not open for writing.\n");
  fwrite($f, "SET NAMES utf8;\n");
  return $f;
}

function convert_schema(PDO $db, $f)
{
  $tables = array();

  $sth = $db->prepare("SELECT `type`, `name`, `sql` FROM `sqlite_master` WHERE `name` NOT LIKE 'sqlite_%'");
  $sth->execute();

  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    if ('table' == $row['type']) {
      if (!in_array($row['name'], $tables))
        $tables[] = $row['name'];
      fprintf($f, "DROP TABLE IF EXISTS `%s`; ", $row['name']);
      $row['sql'] = str_replace(
        array(
          'AUTOINCREMENT',
          '`name_lc` TEXT',
        ), array(
          'AUTO_INCREMENT',
          '`name_lc` VARCHAR(255)',
        ), $row['sql']);

      $row['sql'] = preg_replace('|(int[^P]+) PRIMARY KEY[^,]*,|', '\1 PRIMARY KEY AUTO_INCREMENT,', $row['sql']);
      $row['sql'] .= " DEFAULT CHARSET utf8";
    }
    fwrite($f, $row['sql'] . ";\n");
  }

  return $tables;
}

function convert_data(PDO $db, $f, array $tables)
{
  foreach ($tables as $table) {
    $sth = $db->prepare("SELECT * FROM `{$table}`");
    $sth->execute();

    $prefix = null;

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      foreach ($row as $k => $v) {
        if (null === $v)
          $row[$k] = 'NULL';
        elseif (!is_numeric($v))
          $row[$k] = "'" . mysql_escape_string($v) . "'";
      }

      if (null === $prefix)
        $prefix = "INSERT INTO `{$table}` (`" . join('`,`', array_keys($row)) .  "`) VALUES\n ";
      else
        $prefix = ",\n ";

      $sql = $prefix . "(" . join(',', $row) . ")";
      fwrite($f, $sql);
    }

    if (null !== $prefix)
      fwrite($f, ";\n");
  }
}

function convert($dbFileName, $sqlFileName)
{
  $db = get_pdo($dbFileName);
  $f = get_file($sqlFileName);

  convert_data($db, $f, convert_schema($db, $f));

  fclose($f);
}

if (!function_exists('mysql_escape_string'))
  die("You need the mysql extension (to escape strings correctly).\n");

if (empty($argv[2]))
  die("Usage: sqlite2mysql.php input.db output.sql\n");
elseif (!file_exists($argv[1]))
  die($argv[1] . ": not found.\n");

convert($argv[1], $argv[2]);
