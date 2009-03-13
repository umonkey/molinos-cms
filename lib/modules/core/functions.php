<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

function t($message, array $argv = array())
{
  foreach ($argv as $k => $v) {
    switch (substr($k, 0, 1)) {
    case '!':
    case '%':
      $message = str_replace($k, $v, $message);
      break;
    case '@':
      $message = str_replace($k, htmlspecialchars($v), $message);
      break;
    }
  }

  return $message;
}
