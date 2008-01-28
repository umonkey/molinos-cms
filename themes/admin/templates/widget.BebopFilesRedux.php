<?php

if (!empty($documents)) {
    print "<div id='{$instance}'>";
    print "<h2>Подбор файла</h2>";

    foreach ($documents as $node) {
        $icon = "/themes/admin/mime/{$node['filetype']}.png";

        if (in_array($node['filetype'], array('image/jpeg', 'image/png', 'image/gif'))) {
            if (file_exists('attachments/'. $node['filepath']))
                $icon = "/attachment/{$node['id']},48,52,cdw";
        }

        print "<div class='attachment'>";
        print "<a href='/attachment/{$node['id']}' class='jqmClose'>";
        print "<img src='{$icon}' alt='thumbnail' border='0' />";
        print "<br />". htmlspecialchars($node['name']);
        print "</a>";
        print "</div>";
    }

    if (!empty($pager)) {
      print "<table class='pager'><tr>";

      if (!empty($pager['prev']))
        print "<td><a href='{$pager['prev']}'>&larr;</a></td>";

      foreach ($pager['list'] as $k => $v) {
        print "<td>";

        if (empty($v))
            print "<strong>{$k}</strong>";
        else
            print "<a href='{$v}'>{$k}</a>";

        print "</td>";
      }

      if (!empty($pager['next']))
        print "<td><a href='{$pager['next']}'>&rarr;</a></td>";

      print "</tr></td>";
    }

    print "</div>"; // id=$instance
}
