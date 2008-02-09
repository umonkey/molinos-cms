<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

function smarty_function_tagger_get_nodes($params, &$smarty)
{
  if (!empty($params['tags']) and !empty($params['assign'])) {
    $tags = is_array($params['tags']) ? $params['tags'] : array($params['tags']);

    // Добавляем фильтрацию по типу.
    $filters = empty($params['class']) ? null : array("`n`.`class` = '". $params['class'] ."'");

    $nodes = Tagger::getInstance()->getDocumentsFor($tags, null, $filters);

    $smarty->assign($params['assign'], $nodes);
  }
}
