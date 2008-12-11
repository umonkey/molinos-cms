<?php

class AutoUpdateUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
    switch ($ctx->get('mode')) {
    case 'update':
      return self::onGetUpdate($ctx);
    default:
      return self::onGetDefault($ctx);
    }
  }

  private static function onGetDefault(Context $ctx)
  {
    $ini = AutoUpdater::getLocalModules();

    $form = new Form(array(
      'title' => t('Управление модулями CMS'),
      'action' => '?q=autoupdate.rpc&action=confirm&mode=enable&destination=' . $ctx->get('destination', 'CURRENT'),
      ));
    $form->addControl(new AutoUpdateTableControl(array(
      'value' => 'modules',
      'label' => t('Доступные модули'),
      'columns' => array(
        'check',
        'name',
        'version',
        'settings',
        ),
      )));
    $form->addControl(new SubmitControl(array(
      'text' => t('Сохранить изменения'),
      )));

    $data = Control::data(array(
      'modules' => $ini,
      ));

    return $form->getHTML($data);
  }

  private static function onGetUpdate(Context $ctx)
  {
    if (null === ($ini = AutoUpdater::getUpdatedModules()))
      return;

    $form = new Form(array(
      'title' => t('Обновление модулей CMS'),
      'action' => '?q=autoupdate.rpc&action=confirm&mode=update&destination=' . $ctx->get('destination', 'CURRENT'),
      ));
    $form->addControl(new AutoUpdateTableControl(array(
      'value' => 'modules',
      'label' => t('Доступные модули'),
      'columns' => array(
        'name',
        'version',
        'available',
        ),
      )));
    $form->addControl(new SubmitControl(array(
      'text' => t('Обновить'),
      )));

    $data = Control::data(array(
      'modules' => $ini,
      ));

    return $form->getHTML($data);
  }
}
