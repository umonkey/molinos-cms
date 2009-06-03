<?php
// Structure Migration Assistant.

class StructureMA
{
  private $access = array();

  public function import()
  {
    $this->getAccess();

    return array(
      'access' => $this->access,
      );
  }

  private function getAccess()
  {
    $groups = $types = array();

    $data = Context::last()->db->getResults("SELECT `a`.`uid`, `n`.`name`, `a`.`c`, `a`.`r`, `a`.`u`, `a`.`d`, `a`.`p` FROM `node__access` `a` INNER JOIN `node` `n` ON `n`.`id` = `a`.`nid` WHERE `n`.`deleted` = 0 AND `n`.`class` = 'type' ORDER BY `a`.`uid`");

    foreach ($data as $row) {
      $gid = $row['uid']
        ? 'group:' . $row['uid']
        : 'anonymous';

      foreach (array('c', 'r', 'u', 'd', 'p') as $key) {
        if ($row[$key] and (empty($groups[$gid][$key]) or !in_array($row['name'], $groups[$gid][$key])))
          $groups[$gid][$key][] = $row['name'];
      }
    }

    $data = Node::find(array(
      'class' => 'type',
      'deleted' => 0,
      ));

    foreach ($data as $row)
      if (is_array($row->perm_own))
        $types[$row->name] = $row->perm_own;

    $this->access = array(
      'groups' => $groups,
      'types' => $types,
      );
  }
}
