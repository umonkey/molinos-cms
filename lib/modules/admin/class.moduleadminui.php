<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleAdminUI
{
  public function getList()
  {
    $form = self::getFormFields()->getForm(array(
      'title' => t('Управление модулями'),
      'action' => '?q=admin.rpc&action=modenable&destination=CURRENT',
      ));

    return $form->getHTML(Control::data(array(
      'modules' => modman::getLocalModules(),
      )));
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

  protected static function getFormFields()
  {
    return new Schema(array(
      'modules' => array(
        'type' => 'ModManControl',
        'label' => t('Доступные модули'),
        'columns' => array(
          'check',
          'settings',
          'name',
          'version',
          ),
        ),
      'submit' => array(
        'type' => 'SubmitControl',
        'text' => t('Сохранить изменения'),
        ),
      ));
  }
};
