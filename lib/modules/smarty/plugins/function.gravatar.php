<?php

function smarty_function_gravatar($params, &$smarty)
{
  if (empty($params['user']))
    $user = mcms::user();
  elseif (is_array($params['user']))
    $user = Node::create('user', $params['user']);
  elseif ($params['user'] instanceof Node)
    $user = $params['user'];
  else
    throw new InvalidArgumentException(t('Параметр «user» для {gravatar} должен быть массивом или объектом Node.'));

  $size = empty($params['size']) ? 50 : $params['size'];
  $default = null;

  foreach ((array)$user->files as $f) {
    if (substr($f['filetype'], 0, 6) == 'image/') {
      $default = 'http://'. $_SERVER['HTTP_HOST'] ."attachment/{$f['id']},{$size},{$size},c";
      break;
    }
  }

  $url = array(
    'host' => 'www.gravatar.com',
    'path' => '/avatar.php',
    'args' => array(
      'gravatar_id' => md5($user->email),
      'size' => $size,
      'default' => $default,
      ),
    );

  return mcms::html('img', array(
    'class' => empty($params['class']) ? null : $params['class'],
    'src' => bebop_combine_url($url, false),
    'width' => $size,
    'height' => $size,
    'alt' => $user->name,
    ));
}
