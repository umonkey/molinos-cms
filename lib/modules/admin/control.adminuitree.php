<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUITreeControl extends AdminUIListControl implements iFormControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Дерево объектов'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('columns'));
  }

  public function getHTML(array $data)
  {
    $output = '<table class=\'nodelist\'>';
    $output .= $this->getTableHeader();

    foreach ($data['nodes'] as $nid => $node) {
      $row = "<td class='selector'><input type='checkbox' name='nodes[]' value='{$nid}' /></td>";

      foreach ($this->columns as $field) {
        $value = array_key_exists($field, $node) ? $node[$field] : null;

        $row .= "<td class='field-{$field}'>";

        if (empty($value)) {
          $row .= '&nbsp;';
        } else {
          $row .= $value;
        }

        $row .= '</td>';
      }

      $parent = empty($node['parent_id']) ? null : $node['parent_id'];

      $row .= '<td class=\'actions\'>';
      $row .= mcms::html('a', array(
        'href' => "/nodeapi.rpc?action=raise&node={$nid}&parent={$parent}&destination=". urlencode($_SERVER['REQUEST_URI'])
        ), 'поднять');
      $row .= mcms::html('a', array(
        'href' => "/nodeapi.rpc?action=sink&node={$nid}&parent={$parent}&destination=". urlencode($_SERVER['REQUEST_URI'])
        ), 'опустить');
      $row .= mcms::html('a', array(
        'href' => "/admin/?mode=create&type={$node['class']}&parent={$nid}&destination=". urlencode($_SERVER['REQUEST_URI'])
        ), 'добавить');
      $row .= '</td>';

      $output .= mcms::html('tr', array(
        'class' => empty($node['published']) ? 'unpublished' : 'published',
        ), $row);
    }

    $output .= '</table>';

    return $output;
  }

  private function getTableHeader()
  {
    $output = '<tr>';
    $output .= '<th class=\'selector\'>&nbsp;</th>';

    foreach ($this->columns as $col) {
      $output .= mcms::html('th', array(
        'class' => 'field-'. $col,
        ), $col);
    }

    $output .= '<th>Действия</th>';

    return $output .= '</tr>';
  }
};
