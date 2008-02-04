<?php

if (!empty($list)) {
  print "<ul id='top_menu'>";

  foreach ($list as $item) {
    $icon = mcms::html('img', array(
      'src' => $item['img'],
      'alt' => $item['name'],
      )) .'<br/>'. $item['title'];

    $icon = mcms::html('span', array(
      'style' => 'background-image: url('. $item['img'] .')'
      ));

    $icon .= $item['title'];

    $icon = mcms::html('a', array(
      'href' => $item['href'],
      ), $icon);
    $icon = mcms::html('li', array(
      'title' => $item['description'],
      ), $icon);
    print $icon;
  }

  print "</ul>";
}
