<?php

class ModManUI
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

  private static function getXML(Context $ctx, array $list, array $options)
  {
    $ctx->theme = os::path('lib', 'modules', 'modman', 'template.xsl');

    $output = '';

    foreach ($list as $k => $v)
      $output .= html::em('module', array('id' => $k) + $v);

    return html::em('modules', $options, $output);
  }

  protected static function aui_config(Context $ctx)
  {
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

  public static function on_get_install(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'modman', 'template.xsl');

    if (!count($modules = modman::getAllModules())) {
      modman::updateDB();
      $modules = modman::getAllModules();
    }

    $count = count($modules);

    // Удаляем из списка обязательные модули: их нельзя отключать.
    // Это, за одно, позволит дробить модули без захламления интерфейса
    // и смущения пользователя.
    foreach ($modules as $k => $v) {
      if (!empty($v['installed']))
        unset($modules[$k]);
      elseif ('deprecated' == $v['priority'])
        unset($modules[$k]);
    }

    return self::getXML2($ctx, $modules, array(
      'mode' => 'install',
      'title' => t('Установка модулей'),
      'release' => MCMS_RELEASE,
      'count' => $count,
      ));
  }

  public static function on_get_remove(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'modman', 'template.xsl');

    if (!count($modules = modman::getAllModules())) {
      modman::updateDB();
      $modules = modman::getAllModules();
    }

    // Удаляем из списка обязательные модули: их нельзя отключать.
    // Это, за одно, позволит дробить модули без захламления интерфейса
    // и смущения пользователя.
    foreach ($modules as $k => $v)
      if (empty($v['installed']) or 'required' == @$v['priority'])
        unset($modules[$k]);

    return self::getXML2($ctx, $modules, array(
      'mode' => 'remove',
      'title' => t('Удаление модулей'),
      ));
  }

  public static function on_get_upgrade(Context $ctx)
  {
    $ctx->theme = os::path('lib', 'modules', 'modman', 'template.xsl');

    $modules = modman::getUpdatedModules();

    if (empty($modules)) {
      // FIXME: когда перестанут сбрасывать кэш при каждом вздохе, всем станет лучше.
      modman::updateDB();
      $modules = modman::getUpdatedModules();
    }

    // Удаляем из списка обязательные модули: их нельзя отключать.
    // Это, за одно, позволит дробить модули без захламления интерфейса
    // и смущения пользователя.
    foreach ($modules as $k => $v)
      if (empty($v['installed']))
        unset($modules[$k]);

    return self::getXML2($ctx, $modules, array(
      'mode' => 'upgrade',
      'title' => t('Обновление модулей'),
      ));
  }

  public static function on_reload(Context $ctx)
  {
    modman::updateDB();
    return $ctx->getRedirect();
  }

  private static function getXML2(Context $ctx, array $list, array $options)
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

    return html::em('content', $options, $output);
  }
}
