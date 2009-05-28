<?php
/**
 * Поддержка расширенных индексов для Molinos CMS.
 *
 * Обрабатывает сохранение документов.  Если сохраняется тип (class=type),
 * проверяет наличие нужных таблиц, затем индексирует отсутствующие документы.
 * Индексирование документов выполняется по несколько штук за транзакцию, чтобы
 * можно было продолжить в случае убийства скрипта по причине нехватки времени.
 *
 * Если сохраняется обычный документ, проверяется наличие у него дополнительных
 * индексов и они обновляются.
 *
 * @author Justin Forest <justin.forest@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html
 */

class Indexer
{
  /**
   * @mcms_message ru.molinos.cms.hook.node.before
   */
  public static function on_save(Context $ctx, Node $node, $op)
  {
    if ('type' == $node->class)
      return self::update_type($ctx, $node, $op);
    else
      return self::update_other($ctx, $node, $op);
  }

  private static function update_type(Context $ctx, Node $node, $op)
  {
    if ($op == 'create' or $op == 'update') {
      self::recreateIndexes($ctx, $node);
      self::reindexMissingNodes($ctx, $node);
    }
  }

  private static function update_other(Context $ctx, Node $node, $op)
  {
    if ($op == 'create' or $op == 'update') {
      $schema = $node->getSchema();

      foreach ($schema->getIndexes() as $fieldName) {
        $sql = "REPLACE INTO `node__idx_{$fieldName}` (`id`, `value`) VALUES (%ID%, ?)";
        $value = $schema[$fieldName]->getIndexValue($node->{$fieldName});

        $node->onSave($sql, array($value));
      }
    }
  }

  /**
   * Создание всех индексов, описанных в типе.
   */
  private static function recreateIndexes(Context $ctx, Node $type)
  {
    foreach ((array)$type->fields as $name => $info)
      if (!NodeStub::isBasicField($name) and !empty($info['indexed'])) {
        if ($sql = Control::getIndexType($info['type'])) {
          TableInfo::check('node__idx_' . $name, array(
            'id' => array(
              'type' => 'integer',
              'required' => true,
              'key' => 'pri',
              ),
            'value' => array(
              'type' => $sql,
              'required' => false,
              'key' => 'mul',
              ),
            ));
        }
      }
  }

  /**
   * Индексация документов, отсутствующих в индексе.
   */
  private static function reindexMissingNodes(Context $ctx, Node $type)
  {
    $schema = Schema::load($ctx->db, $type->name);

    foreach ($schema->getIndexes() as $fieldName) {
      $tableName = 'node__idx_' . $fieldName;

      $sel = $ctx->db->prepare("SELECT `id` FROM `node` WHERE `class` = ? AND `deleted` = 0 AND `id` NOT IN (SELECT `id` FROM `{$tableName}`)");
      $sel->execute(array($type->name));

      $upd = $ctx->db->prepare("INSERT INTO `{$tableName}` (`id`, `value`) VALUES (?, ?)");

      $count = 0;
      while ($nid = $sel->fetchColumn(0)) {
        $node = Node::load($nid, $ctx->db);
        $upd->execute(array(
          $nid,
          $schema[$fieldName]->getIndexValue($node->$fieldName),
          ));
      }
    }
  }
}
