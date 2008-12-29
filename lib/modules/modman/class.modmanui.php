<?php

class ModManUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
    $method = 'aui_' . $ctx->get('mode', 'settings');

    if (method_exists(__CLASS__, $method))
      return call_user_func(array(__CLASS__, $method), $ctx);
    else
      throw new PageNotFoundException();

    mcms::debug($ctx, $result);

    $modules = modman::getUpdatedModules();

    if (null === ($mode = $ctx->get('mode')))
      $mode = empty($modules)
        ? 'update'
        : 'upgrade';

    $form = self::getForm($mode);

    if (is_array($status = $ctx->get('status'))) {
      if (!empty($status['updated']))
        $form->intro .= t('Были обновлены модули: %list.', array(
          '%list' => implode(', ', $status['updated']),
          ));
    }

    return $form->getHTML(Control::data(array(
      'modules' => $modules,
      )));
  }

  protected static function aui_settings(Context $ctx)
  {
    $schema = new Schema(array(
      'modules' => array(
        'type' => 'ModManControl',
        'label' => t('Настройка модулей'),
        'columns' => array(
          'settings',
          'name',
          'version',
          ),
        ),
      ));

    $form = $schema->getForm(array(
      'title' => t('Настройка модулей'),
      'intro' => t('Здесь видны только модули, предполагающие настройку. Вы можете <a href="@url">установить дополнительные модули</a>.', array(
        '@url' => '?q=admin&cgroup=system&module=modman&mode=addremove&destination=CURRENT',
        )),
      ));

    return $form->getHTML(Control::data(array(
      'modules' => modman::getConfigurableModules(),
      )));
  }

  protected static function aui_addremove(Context $ctx)
  {
    $schema = new Schema(array(
      'modules' => array(
        'type' => 'ModManControl',
        'columns' => array(
          'check',
          'name',
          'version',
          ),
        ),
      'submit' => array(
        'type' => 'SubmitControl',
        'text' => t('Сохранить изменения'),
        ),
      ));

    $form = $schema->getForm(array(
      'title' => t('Установка и удаление модулей'),
      'action' => '?q=modman.rpc&action=addremove&destination='
        . urlencode($ctx->get('destination', 'CURRENT')),
      ));

    if (!count($modules = modman::getAllModules()))
      $modules = modman::updateDB();

    return $form->getHTML(Control::data(array(
      'modules' => $modules,
      )));
  }

  protected static function aui_config(Context $ctx)
  {
    if (!array_key_exists($name = $ctx->get('name'), modman::getConfigurableModules()))
      throw new PageNotFoundException();

    $form = mcms::invoke_module($name, 'iModuleConfig', 'formGetModuleConfig');

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

    $html = $form->getHTML(Control::data($data));

    return $html;
  }

  protected static function aui_upgrade(Context $ctx)
  {
    $schema = new Schema(array(
      'modules' => array(
        'type' => 'ModManControl',
        'columns' => array(
          'check',
          'name',
          'version',
          'available',
          ),
        ),
      'submit' => array(
        'type' => 'SubmitControl',
        'text' => t('Обновить отмеченные'),
        ),
      ));

    $form = $schema->getForm(array(
      'title' => t('Обновление модулей'),
      'action' => '?q=modman.rpc&action=upgrade&destination='
        . urlencode($ctx->get('destination', 'CURRENT')),
      ));

    return $form->getHTML(Control::data(array(
      'modules' => modman::getUpdatedModules(),
      )));
  }

  protected static function getForm($mode)
  {
    $form = self::getSchema($mode)->getForm(array(
      'title' => t('Обновление системы'),
      'action' => '?q=update.rpc&action=' . urlencode($mode) . '&destination=CURRENT',
      ));

    switch ($mode) {
    case 'update':
      $form->title = t('Обновлений нет');
      break;
    }

    return $form;
  }

  protected static function getSchema($mode)
  {
    switch ($mode) {
    case 'update':
      $sources = modman::getSources();

      return new Schema(array(
        'info' => array(
          'type' => 'InfoControl',
          'text' => t('Система полностью обновлена (наличие обновлений проверяется в фоновом режиме, по расписанию).'),
          ),
        'submit' => array(
          'type' => 'SubmitControl',
          'text' => t('Проверить наличие обновлений'),
          ),
        ));
    case 'upgrade':
      return new Schema(array(
        'modules' => array(
          'type' => 'ModManControl',
          'label' => t('Доступные обновления'),
          'columns' => array(
            'check',
            'name',
            'version',
            'available',
            ),
          ),
        'submit' => array(
          'type' => 'SubmitControl',
          'text' => t('Обновить отмеченные'),
          ),
        ));
    default:
      mcms::debug($mode);
    }
  }
}
