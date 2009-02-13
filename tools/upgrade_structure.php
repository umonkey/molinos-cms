<?php

function connect($dsn)
{
  $u = parse_url($dsn);

  switch ($u['scheme']) {
  case 'sqlite':
    $db = new PDO($dsn, '');
    break;
  default:
    die("Unsupported schema: {$u['scheme']}.\n");
  }

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $db;
}

function upgrade($dsn)
{
  $db = connect($dsn);
  $db->beginTransaction();

  $db->exec("ALTER TABLE `node` RENAME TO `node2`");
  $db->exec("CREATE TABLE `node` (`id` integer NOT NULL PRIMARY KEY, `lang` char(4) NOT NULL, `parent_id` integer NULL, `class` varchar(16) NOT NULL, `left` integer NULL, `right` integer NULL, `uid` integer NULL, `created` datetime NULL, `updated` datetime NULL, `published` tinyint(1) NOT NULL DEFAULT '0', `deleted` tinyint(1) NOT NULL DEFAULT '0', `name` VARCHAR(255) NULL, `name_lc` VARCHAR(255) NULL, `data` MEDIUMBLOB NULL)");
  $db->exec("INSERT INTO `node` (`id`, `lang`, `parent_id`, `class`, `left`, `right`, `uid`, `created`, `updated`, `published`, `deleted`, `name`, `name_lc`, `data`) SELECT `n`.`id`, `n`.`lang`, `n`.`parent_id`, `n`.`class`, `n`.`left`, `n`.`right`, `n`.`uid`, `n`.`created`, `n`.`updated`, `n`.`published`, `n`.`deleted`, `v`.`name`, `v`.`name_lc`, `v`.`data` FROM `node2` `n` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid`");
  $db->exec("DROP TABLE `node2`");

  foreach (array('lang', 'parent_id', 'class', 'left', 'right', 'uid', 'created', 'updated', 'published', 'deleted', 'name', 'name_lc') as $idx)
    $db->exec("CREATE INDEX `IDX_node_{$idx}` ON `node` (`{$idx}`)");

  foreach (array('node__rev', 'node__cache', 'node__astat', 'node__seq') as $table)
    $db->exec("DROP TABLE IF EXISTS `{$table}`");

  $db->commit();
  return 0;
}

if (empty($argv[1])) {
  printf("Usage: %s dsn\n", basename($argv[0]));
  exit(1);
}

return upgrade($argv[1]);
