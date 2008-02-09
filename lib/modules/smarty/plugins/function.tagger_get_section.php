<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

function smarty_function_tagger_get_section($params, &$smarty)
{
  if (!empty($params['root']) and !empty($params['assign'])) {
    $nid = is_array($params['root']) ? $params['root']['id'] : $params['root'];
    $node = Node::load($nid);

    $smarty->assign($params['assign'], $node->getChildren('nested'));
  }
}
