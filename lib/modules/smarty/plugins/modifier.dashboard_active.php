<?php

function smarty_modifier_dashboard_active($html)
{
  $parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

  if (!empty($parts[1])) {
    $mark = "dashboard-item-{$parts[1]}";
    $html = str_replace(" id='{$mark}'", " id='{$mark}' class='current'", $html);
  }

  return $html;
}
