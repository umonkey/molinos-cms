<?php
/**
 * Массовое обновление XML представления нод для Molinos CMS.
 *
 * Используется для форсированного обновления XML после изменения структуры
 * полей документов какого-то типа.  Обычно XML обновляется при редактировании
 * объекта, однако иногда объектов слишком много, чтобы их можно было обновить
 * вручную.
 *
 * Выполнение этого действия через веб тоже не всегда возможно, т.к. если объектов
 * много, скрипту может не хватить времени на завершение транзакции.
 *
 * Пример использования: php -f tools/nodeupdater.php story
 */

require_once dirname(__FILE__) . '/client.inc';

if (empty($argv[1]))
  die("Usage: php -f tools/nodeupdater.php typeName\n");

$db = Context::last()->db;

$errors = array();
$ids = $db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0", array($argv[1]));
$count = count($ids);

$db->beginTransaction();
foreach ($ids as $idx => $id) {
  try {
    $node = Node::load($id, $db)->updateXML();
    printf("[%5u/%u] node[%d]: OK (%s, %s)\n", $idx+1, $count, $id, $node->class, $node->getName());
  } catch (Exception $e) {
    printf("[%5u/%u] node[%d]: ERROR\n -- %s\n", $idx+1, $count, $id, trim($e->getMessage()));
    $errors[$id] = $e->getMessage();
  }
}

printf("%u errors.\n", count($errors));
foreach ($errors as $id => $message)
  printf(" - node[%u]: %s\n", $id, trim($message));
printf("COMMIT\n");
$db->commit();
