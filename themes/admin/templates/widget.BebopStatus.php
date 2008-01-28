<?php

if (!empty($hints)) {
  print "<h2>Отчёт о состоянии системы</h2>";
  print "<ul>";

  foreach ($hints as $hint)
    print "<li>{$hint}</li>";

  print "</ul>";
}
