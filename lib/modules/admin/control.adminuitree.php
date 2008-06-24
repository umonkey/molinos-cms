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
    $output = '<table class=\'mcms nodelist\'>';
    $output .= $this->getTableHeader();

    foreach ($data['nodes'] as $nid => $node) {
      $row = "<td class='selector'><input type='checkbox' name='nodes[]' value='{$nid}' /></td>";

      $parent = empty($node['parent_id']) ? null : $node['parent_id'];

      $row .= $this->getActions($node);

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
    $output .= '<th class=\'actions\'>&nbsp;</th>';

    if (!is_array($titles = $this->columntitles))
      $titles = array();

    foreach ($this->columns as $col) {
      $title = array_key_exists($col, $titles)
        ? $titles[$col]
        : $col;

      $output .= mcms::html('th', array(
        'class' => 'field-'. $col,
        ), $title);
    }

    return $output .= '</tr>';
  }

  private function getActions(array $node)
  {
    $output = array();

    if (null !== ($tmp = $this->getDebugLink($node)))
      $output[] = $tmp;
    if (null !== ($tmp = $this->getRaiseLink($node)))
      $output[] = $tmp;
    if (null !== ($tmp = $this->getSinkLink($node)))
      $output[] = $tmp;
    if (null !== ($tmp = $this->getZoomLink($node)))
      $output[] = $tmp;
    if (null !== ($tmp = $this->getAddLink($node)))
      $output[] = $tmp;

    if (!empty($output)) {
      return mcms::html('td', array(
        'class' => 'actions',
        'style' => 'padding-left: 4px; padding-right: 4px; width: '. (18 * count($output)) .'px',
        ), join('', $output));
    }
  }

  private function getDebugLink(array $node)
  {
    if (bebop_is_debugger() and !empty($node['id']))
      return $this->getIcon('lib/modules/admin/img/debug.gif', "nodeapi.rpc?action=dump&node={$node['id']}", t('Поднять'));
  }

  private function getRaiseLink(array $node)
  {
    if (!empty($node['id']))
      return $this->getIcon('themes/admin/img/moveup.png', "nodeapi.rpc?action=raise&node={$node['id']}&destination=CURRENT", t('Поднять'));
  }

  private function getSinkLink(array $node)
  {
    if (!empty($node['id']))
      return $this->getIcon('themes/admin/img/movedown.png', "nodeapi.rpc?action=sink&node={$node['id']}&destination=CURRENT", t('Поднять'));
  }

  private function getZoomLink(array $node)
  {
    if (!$this->zoomlink)
      return;

    if (empty($node['class']) or empty($node['id']) or !mcms::user()->hasAccess('u', $node['class']))
      return;

    if (!empty($node['deleted']))
      return;

    return $this->getIcon('themes/admin/img/zoom.png', str_replace(array('NODEID', 'NODENAME'), array($node['id'], $node['name']), $this->zoomlink), t('Найти'));
  }

  private function getAddLink(array $node)
  {
    if (mcms::user()->hasAccess('c', $node['class']))
      return $this->getIcon('themes/admin/img/icon-add.png', "admin?cgroup={$_GET['cgroup']}&mode=create&type={$node['class']}&destination=CURRENT", 'title');
  }

  private function getIcon($img, $href, $title)
  {
    $path = substr($img, 0);

    if (!is_readable($path))
      return;

    $tmp = mcms::html('img', array(
      'src' => $img,
      'width' => 16,
      'height' => 16,
      'alt' => $title,
      ));

    return mcms::html('a', array(
      'class' => 'icon',
      'href' => $href,
      'title' => $title,
      ), $tmp);
  }
};
