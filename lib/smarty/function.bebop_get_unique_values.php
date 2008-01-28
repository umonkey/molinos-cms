<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

function smarty_function_bebop_get_unique_values($params, &$smarty)
{
  $message = null;
  $result = null;

  $cache = BebopCache::getInstance();

  if (empty($params['type'])) {
    $message = t("Не указан тип документа, используйте параметр type.");
  } else {
    $key = 'bebop_unique_value_for_'. $params['type'];

    if (!is_array($result = $cache->$key)) {
      $result = array();

      $pdo = PDO_Singleton::getInstance();
      $schema = TypeNode::getSchema($params['type']);

      foreach ($schema['fields'] as $field => $meta) {
        if (!empty($meta['indexed'])) {
          $result[$field] = $pdo->getResultsV($field, "SELECT DISTINCT `f`.`{$field}` FROM `node` `n` INNER JOIN `node_{$params['type']}` `f` ON `f`.`rid` = `n`.`rid` WHERE `n`.`class` = :type ORDER BY `{$field}`", array(':type' => $params['type']));
        }
      }

      $cache->$key = $result;
    }
  }

  if (empty($params['assign']))
    $message = t("Не указана переменная, в которую нужно вернуть результат, используйте параметр assign.");
  elseif ($result !== null)
    $smarty->assign($params['assign'], $result);

  if ($message !== null)
    return "<p><strong>bebop_get_unique_values: ". $message ."</strong></p>";
}
