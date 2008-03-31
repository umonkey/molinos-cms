<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleAdminUI
{
  public function getList()
  {
    $map = $this->getModules();

    $output = '';

    foreach ($map as $group => $modules) {
      $output .= "<tr class='modgroup'><th colspan='4'>{$group}</th></tr>";

      foreach ($modules as $modname => $module) {
        $output .= '<tr>';

        $output .= "<td>". mcms::html('input', array(
          'type' => 'checkbox',
          'name' => 'selected[]',
          'value' => $modname,
          'checked' => empty($module['enabled']) ? null : 'checked',
          'disabled' => ('core' == strtolower($group)) ? 'disabled' : null,
          )) ."</td>";

        if (!empty($module['implementors']['iModuleConfig']) and !empty($module['enabled']))
          $output .= "<td><a class='configure' href='/admin/?mode=modules&amp;action=config&amp;name={$modname}&amp;cgroup={$_GET['cgroup']}'><span>настроить</span></a></td>";
        else
          $output .= "<td>&nbsp;</td>";

        $output .= "<td><a href='/admin/?mode=modules&amp;action=info&amp;name={$modname}&amp;cgroup={$_GET['cgroup']}'>{$modname}</a></td>";
        $output .= "<td>{$module['name']['ru']}</td>";

        $output .= '</tr>';
      }
    }

    $output = mcms::html('table', array(
      'class' => 'modlist',
      ), $output);

    $output .= mcms::html('input', array(
      'type' => 'submit',
      'value' => t('Сохранить'),
      ));

    return '<h2>Список модулей</h2>'. mcms::html('form', array(
      'method' => 'post',
      'action' => $_SERVER['REQUEST_URI'],
      ), $output);
  }

  private function getModules()
  {
    $map = mcms::getModuleMap();

    $groups = array();

    foreach ($map['modules'] as $modname => $module)
      $groups[$module['group']][$modname] = $module;

    ksort($groups);

    return $groups;
  }
};
