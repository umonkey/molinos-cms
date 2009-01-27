<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

function fix_sqlite(array $url)
{
  $pdo = new mcms_sqlite_driver($url);

  $row = $pdo->fetch("SELECT * FROM node__rev LIMIT 1");
  if (!array_key_exists('data_enc', $row)) {
    printf("Adding node__rev.data_enc to %s\n", $url['path']);
    $pdo->exec("ALTER TABLE node__rev ADD COLUMN data_enc MEDIUMBLOB");
  }

  $sth1 = $pdo->prepare("SELECT rid, data FROM node__rev");
  $sth2 = $pdo->prepare("UPDATE node__rev SET data = NULL, data_enc = ? WHERE rid = ?");

  $sth1->execute();

  while ($row = $sth1->fetch(PDO::FETCH_ASSOC))
    $sth2->execute(array(base64_encode($row['data']), $row['rid']));
}

function fix_mysql(array $url)
{
  $pdo = new mcms_mysql_driver($url);

  $sth1 = $pdo->prepare("SELECT rid, data_enc FROM node__rev");
  $sth2 = $pdo->prepare("UPDATE node__rev SET data = ? WHERE rid = ?");

  $sth1->execute();
  $count = 0;

  while ($row = $sth1->fetch(PDO::FETCH_ASSOC)) {
    $sth2->execute(array(base64_decode($row['data_enc']), $row['rid']));
    $count++;
  }

  printf("%u rows updated.\n", $count);

  $pdo->exec("ALTER TABLE node__rev DROP COLUMN data_enc");
}

if ($argc < 2)
  die(sprintf("Usage: %s url\n", basename($argv[0])));

$url = parse_url($argv[1]);

try {
  switch ($url['scheme']) {
  case 'sqlite':
    fix_sqlite($url);
    break;
  case 'mysql':
    fix_mysql($url);
    break;
  }
} catch (Exception $e) {
  printf("ERROR: %s\n", $e->getMessage());
  exit(1);
}
