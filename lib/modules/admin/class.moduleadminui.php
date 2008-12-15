<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleAdminUI
{
  public function getList()
  {
    $form = self::getSchema()->getForm(array(
      'title' => t('Управление модулями'),
      'action' => '?q=admin.rpc&action=modenable&destination=CURRENT',
      ));

    return $form->getHTML(Control::data(array(
      'modules' => modman::getAllModules(),
      )));
  }

  protected static function getSchema()
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
