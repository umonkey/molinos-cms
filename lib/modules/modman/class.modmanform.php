<?php

class ModmanForm implements iAdminForm
{
  public function getAdminFormXML(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'modman', 'template.xsl');

    switch ($ctx->get('mode')) {
    case 'addremove':
      return $this->getInstallForm($ctx);
    case 'config':
      return $this->getConfigForm($ctx);
    case 'upgrade':
      return $this->getUpgradeForm($ctx);
    default:
      return $this->getSettingsForm($ctx);
    }
  }

  protected function getSettingsForm(Context $ctx)
  {
    if (!count($list = modman::getConfigurableModules())) {
      modman::updateDB();
      $list = modman::getConfigurableModules();
    }

    return self::getXML($ctx, modman::getConfigurableModules(), array(
      'mode' => 'settings',
      'title' => t('Настройка модулей'),
      ));
  }

  protected function getConfigForm(Context $ctx)
  {
    if (!array_key_exists($name = $ctx->get('name'), modman::getConfigurableModules()))
      throw new PageNotFoundException();

    $form = mcms::invoke_module($name, 'iModuleConfig', 'formGetModuleConfig');
    if (!($form instanceof iFormControl))
      throw new RuntimeException(t('Модуль %name не поддерживает настройку. Скорее всего, в него совсем недавно были внесены изменения, ручная "перезагрузка" системы поможет.', array(
        '%name' => $name,
        )));

    $data = array();

    if (is_array($tmp = mcms::modconf($name)))
      foreach ($tmp as $k => $v)
        $data['config_'. $k] = $v;

    if (empty($form->title))
      $form->title = t('Настройка модуля %name', array('%name' => $name));

    $form->action = '?q=modman.rpc&action=configure&module=' . $name
      . '&destination=' . urlencode($ctx->get('destination', '?q=admin'));

    $form->addControl(new SubmitControl(array(
      'text' => t('Сохранить'),
      )));

    return html::em('block', array(
      'name' => 'modman',
      'title' => $name,
      'mode' => 'config',
      ), $form->getXML(Control::data($data)));
  }

  protected function getInstallForm(Context $ctx)
  {
    // Пост-инсталляционный хук для новых модулей.
    foreach ($ctx->get('status', array()) as $name => $status)
      if ('installed' == $status) {
        $args = array($ctx);
        mcms::invoke_module($name, 'iInstaller', 'onInstall', $args);
      }

    if (!count($modules = modman::getAllModules())) {
      modman::updateDB();
      $modules = modman::getAllModules();
    }

    // Удаляем из списка обязательные модули: их нельзя отключать.
    // Это, за одно, позволит дробить модули без захламления интерфейса
    // и смущения пользователя.
    foreach ($modules as $k => $v)
      if ('required' == $v['priority'])
        unset($modules[$k]);

    return self::getXML($ctx, $modules, array(
      'mode' => 'addremove',
      'title' => t('Установка и удаление модулей'),
      ));
  }

  private static function getXML(Context $ctx, array $list, array $options)
  {
    $output = '';

    foreach ($list as $k => $v)
      $output .= html::em('module', array('id' => $k) + $v);

    if (is_array($ctx->get('status')))
      foreach ($ctx->get('status') as $k => $v)
        $output .= html::em('status', array(
          'module' => $k,
          'result' => $v,
          ));

    $options['name'] = 'modman';
    $options['mode'] = $ctx->get('mode');

    return html::em('block', $options, $output);
  }
}
