<?php

if (!empty($list)) {
  print "<ul id='top_menu'>";

  foreach ($list as $item) {
    print "<li title='{$item['description']}' id='dashboard-item-{$item['class']}'>";
    print "<a href='{$item['link']}'>";
    print "<span style='background-image: url({$prefix}/img/dashboard-task-{$item['class']}.gif)'></span>";
    print $item['name'];
    print "</a>";
    print "</li>";
  }

  print "</ul>";
}
