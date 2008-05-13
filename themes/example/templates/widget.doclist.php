<?php

if (!empty($documents)) {
  foreach ($documents as $doc) {
    $html = bebop_render_object('teaser', $doc['class'], null, array('doc' => $doc));

    print mcms::html('div', array(
      'class' => 'document '. $doc['class'],
      ), $html);
  }
}
