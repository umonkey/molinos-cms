<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2

function smarty_function_tagger_get_section($params, &$smarty)
{
  if (empty($params['root']) or empty($params['assign']))
    throw new SmartyException(t('{tagger_get_section} требует параметров root и assign.'));

  $filter = array(
    'id' => Node::_id($params['root']),
    'class' => 'tag',
    );

  $node = Node::load($filter);

  $smarty->assign($params['assign'], $node->getChildren('nested'));
}
