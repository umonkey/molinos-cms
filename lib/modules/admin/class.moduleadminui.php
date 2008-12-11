<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleAdminUI
{
  public function getList()
  {
    $map = $this->getModules();
    $writable = Config::getInstance()->isWritable();

    $output = '';

    foreach ($map as $group => $modules) {
      $output .= "<tr class='modgroup'><th colspan='5'>{$group}</th></tr>";

      foreach ($modules as $modname => $module) {
        $enabled = mcms::ismodule($modname);

        if (!strcasecmp($module['group'], 'core'))
          $enabled = true;

        $output .= '<tr>';

        $output .= "<td>". html::em('input', array(
          'type' => 'checkbox',
          'name' => 'selected[]',
          'value' => $modname,
          'checked' => $enabled ? 'checked' : null,
          'disabled' => ('core' == strtolower($group) or !$writable) ? 'disabled' : null,
          )) ."</td>";

        if (!empty($module['implementors']['iModuleConfig']) and $enabled)
          $output .= html::em('td', html::em('a', array(
            'class' => 'icon configure',
            'href' => "?q=admin/structure/modules&action=config&name={$modname}&destination=CURRENT",
            ), '<span>настроить</span>'));
        else
          $output .= html::em('td');

        if (!empty($module['docurl']))
          $output .= "<td>". html::em('a', array(
            'class' => 'icon information',
            'href' => $module['docurl'],
            ), "<span>информация</span>") ."</td>";
        else
          $output .= html::em('td');

        $output .= html::em('td', html::em('a', array(
          'href' => "?q=admin/structure/modules&action=info&name={$modname}"
            ."&destination=CURRENT"), $modname));

        if (!empty($module['name']['ru']))
          $output .= html::em('td', $module['name']['ru']);
        elseif (!empty($module['name']['en']))
          $output .= html::em('td', $module['name']['en']);
        else
          $output .= html::em('td');

        $output .= '</tr>';
      }
    }

    $output = html::em('table', array(
      'class' => 'modlist',
      ), $output);

    if ($writable) {
      $output .= html::em('input', array(
        'type' => 'submit',
        'value' => t('Сохранить'),
        ));
    }

    $html = '<h2>Список модулей</h2>';

    if (!$writable)
      $html .= t('<p class=\'intro\'>Конфигурационный файл закрыт для записи, изменение списка модулей невозможно.</p>');

    $html .= html::em('form', array(
      'method' => 'post',
      'action' => "?q=admin.rpc&action=modlist&destination=CURRENT",
      ), $output);

    return $html;
  }

  private function getModules()
  {
    $map = mcms::getModuleMap();

    $groups = array();

    foreach ($map['modules'] as $modname => $module) {
      if (empty($module['group']))
        continue;
      $groups[$module['group']][$modname] = $module;
    }

    ksort($groups);

    return $groups;
  }
};
