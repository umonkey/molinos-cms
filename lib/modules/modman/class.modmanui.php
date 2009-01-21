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
}
