<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_bebop_render_document($params, &$smarty)
{
  if (empty($params['doc']))
    return '';

  $doc = $params['doc'];
  $schema = Node::create($doc['class'])->schema();

  $output = "<div class='doc-type-{$doc['class']}'>";

  foreach ($schema['fields'] as $field => $meta) {
    if (empty($doc[$field]))
      continue;

    if ($field == 'name') {
      $output .= '<h3>'. mcms_plain($doc['name']) .'</h3>';
      continue;
    }

    switch ($meta['type']) {
      case 'TextAreaControl':
      case 'TextHTMLControl':
        $output .= '<h4>'. $meta['label'] .'</h4>';
        $output .= $doc[$field];
        break;

      case 'TextLineControl':
        $output .= "<div class='field-{$field}'>". $doc[$field] ."</div>";
        break;
    }
  }

  $output .= '</div>';
  return $output;
}
