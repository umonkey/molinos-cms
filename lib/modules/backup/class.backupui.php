<?php

class BackupUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
    $method = 'aui_' . $ctx->get('mode', 'default');

    if (method_exists(__CLASS__, $method))
      return call_user_func(array(__CLASS__, $method), $ctx);
    else
      throw new PageNotFoundException();

    mcms::debug($ctx, $result);
  }

  protected static function aui_default(Context $ctx)
  {
    $schema = new Schema(array(
      'action' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Выберите действие'),
        'required' => true,
        'options' => array(
          'download' => t('Скачать архив сайта'),
          ),
        ),
      'submit' => array(
        'type' => 'SubmitControl',
        'text' => t('Продолжить'),
        ),
      ));

    $form = $schema->getForm(array(
      'title' => t('Архивирование сайта'),
      'action' => '?q=backup.rpc',
      ));

    return $form->getHTML(Control::data(array()));
  }
}
