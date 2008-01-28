<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4

/**
 * Функция предназначена для получения дерева подразделов целевого раздела
 * От функции Tagger.get.section отличается тем, что не дает возможности посмотреть св-ва целевого раздела, зато позволяет обращаться к "веткам" напрямую. 
 *  
 * @return $result Array массив с деревом подразделов
 * @param $params Array параметры, указанные подключальщиком при вызове функции: root - целевой раздел, assign - массив, в котором будут результаты работы функции.
 * @param $smarty Object линк на смарти
 * @todo придумать способ безконфликтной стилизации ошибок, предупреждений и дебага.
 */

function smarty_function_tagger_get_sections($params, &$smarty)
{
  /*
  $errorstyle = 'color:red; font-weight:bold;';
  $warningstyle = 'color:orange; font-weight:bold;';
  */
  
  if (!empty($params['root']) and !empty($params['assign'])) {
    $root = Node::load($params['root']);
    $children = $root->getChildren('nested');

    if (!array_key_exists('children', $children))
      $children['children'] = array();

    $smarty->assign($params['assign'], $children['children']);
  } else {
    return ('<span style="'. $errorstyle .'">Ошибка tagger_get_sections: Вы забыли указать обязательный параметр root или assign при вызове функции.</span>'); 
  }
}
