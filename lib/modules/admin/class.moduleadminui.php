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

        $output .= "<td>". mcms::html('input', array(
          'type' => 'checkbox',
          'name' => 'selected[]',
          'value' => $modname,
          'checked' => $enabled ? 'checked' : null,
          'disabled' => ('core' == strtolower($group) or !$writable) ? 'disabled' : null,
          )) ."</td>";

        if (!empty($module['implementors']['iModuleConfig']) and $enabled)
          $output .= mcms::html('td', mcms::html('a', array(
            'class' => 'icon configure',
            'href' => "?q=admin/structure/modules&action=config&name={$modname}&destination=CURRENT",
            ), '<span>настроить</span>'));
        else
          $output .= mcms::html('td');

        if (!empty($module['docurl']))
          $output .= "<td>". mcms::html('a', array(
            'class' => 'icon information',
            'href' => $module['docurl'],
            ), "<span>информация</span>") ."</td>";
        else
          $output .= mcms::html('td');

        $output .= mcms::html('td', mcms::html('a', array(
          'href' => "?q=admin/structure/modules&action=info&name={$modname}"
            ."&destination=CURRENT"), $modname));

        if (!empty($module['name']['ru']))
          $output .= mcms::html('td', $module['name']['ru']);
        elseif (!empty($module['name']['en']))
          $output .= mcms::html('td', $module['name']['en']);
        else
          $output .= mcms::html('td');

        $output .= '</tr>';
      }
    }

    $output = mcms::html('table', array(
      'class' => 'modlist',
      ), $output);

    if ($writable) {
      $output .= mcms::html('input', array(
        'type' => 'submit',
        'value' => t('Сохранить'),
        ));
    }

    $html = '<h2>Список модулей</h2>';

    if (!$writable)
      $html .= t('<p class=\'intro\'>Конфигурационный файл закрыт для записи, изменение списка модулей невозможно.</p>');

    $html .= mcms::html('form', array(
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
