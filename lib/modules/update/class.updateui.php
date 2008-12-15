<?php

class UpdateUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
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
          'text' => t('Проверить ещё раз'),
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
