<?php

class ModManUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
    if (!mcms::isAdmin())
      throw new ForbiddenException();

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
      'intro' => t('Здесь видны только модули, предполагающие настройку. Вы можете <a href="@url">включить дополнительные модули</a>.', array(
        '@url' => '?q=admin&cgroup=system&module=modman&mode=addremove&destination=CURRENT',
        )),
      ));

    return $form->getHTML(Control::data(array(
      'modules' => modman::getConfigurableModules(),
      )));
  }

  protected static function aui_addremove(Context $ctx)
  {
    $message = null;

    if (is_array($status = $ctx->get('status'))) {
      $configurable = modman::getConfigurableModules();

      $list = '';
      foreach ($status as $name => $s) {
        switch ($s) {
        case 'removed':
          $s = 'удалён';
          break;
        case 'installed':
          if (!array_key_exists($name, $configurable))
            $s = 'установлен';
          else
            $s = t('установлен (<a href="@url">настроить</a>)', array(
              '@url' => '?q=admin&cgroup=system&module=modman&mode=config&name='
                . urlencode($name) . '&destination=CURRENT',
              ));
          break;
        case 'failed':
          $s = 'ошибка';
          break;
        }
        $list .= html::em('li', $name . ': ' . $s);
      }

      if (!empty($list))
        $message = t('<p>Результат работы:</p>!list', array(
          '!list' => html::em('ul', $list),
          ));
    }

    $schema = new Schema(array(
      'filter' => array(
        'type' => 'EnumControl',
        'label' => t('Показать'),
        'required' => true,
        'class' => 'modman-filter',
        'options' => array(
          '' => t('все модули'),
          'installed' => t('только установленные'),
          'uninstalled' => t('не установленные'),
          'section-base' => t('Основная функциональность'),
          'section-admin' => t('Администрирование'),
          'section-core' => t('Ядро'),
          'section-blog' => t('Блоги'),
          'section-spam' => t('Спам'),
          'section-commerce' => t('Коммерция'),
          'section-interaction' => t('Интерактив'),
          'section-performance' => t('Производительность'),
          'section-service' => t('Служебные'),
          'section-multimedia' => t('Мультимедиа'),
          'section-syndication' => t('Обмен данными'),
          'section-templating' => t('Шаблоны'),
          'section-visual' => t('Визуальные редакторы'),
          'section-custom' => t('Локальные'),
          ),
        ),
      'modules' => array(
        'type' => 'ModManControl',
        'columns' => array(
          'check',
          'name',
          'version',
          'download',
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
      'intro' => $message,
      ));

    if (!count($modules = modman::getAllModules())) {
      modman::updateDB();
      $modules = modman::getAllModules();
    }

    ksort($modules);

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
        'disable_required' => false,
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
          'disable_required' => false,
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
