<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_node($params, &$smarty)
{
  $default = array_key_exists('default', $params)
    ? $params['default']
    : null;

  if (array_key_exists('load', $params)) {
    if (empty($params['assign']))
      throw new SmartyException(t('Не указан параметр assign для {node load=...}'));

    $result = Node::load($params['load'])->getRaw();
    $smarty->assign($params['assign'], $result);
  }

  elseif (array_key_exists('link', $params)) {
    if ($params['link'] instanceof Node)
      $node = $params['link']->getRaw();
    else
      $node = $params['link'];

    if (is_numeric($node)) {
      try {
        $node = Node::load($node)->getRaw();
      } catch (ObjectNotFoundException $e) {
        return 'oops.';
      }
    }

    $path = array_key_exists('path', $params)
      ? $params['path']
      : 'node/';

    $a = array();

    if (is_array($node)) {
      $a['href'] = $path . $node['id'];
      if (array_key_exists('text', $params))
        $a['#text'] = $params['text'];
    }

    if (empty($node))
      print $default;
    elseif (is_numeric($node)) {
      $a['#text'] = '#'. $node;
      $a['href'] = $path . $node;
    } elseif (!is_array($node)) {
        throw new SmartyException(t('Параметр link для {node} должен '
          .'содержать описание объекта.'));
    } elseif (!array_key_exists('#text', $a)) {
      if ('user' == $node['class'] and !empty($node['fullname']))
        $a['#text'] = $node['fullname'];
      else
        $a['#text'] = $node['name'];
    }

    if (!empty($params['class']))
      $a['class'] = $params['class'];

    print mcms::html('a', $a, mcms_plain($a['#text']));
  }
}
