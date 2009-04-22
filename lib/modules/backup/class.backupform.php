<?php

class BackupForm
{
  /**
   * @mcms_message ru.molinos.cms.admin.form.backup
   */
  public function getAdminFormXML(Context $ctx)
  {
    $form = new Form(array(
      'action' => '?q=backup.rpc',
      'title' => t('Архивирование сайта'),
      ));

    $form->addControl(new EnumRadioControl(array(
      'value' => 'action',
      'label' => t('Выберите операцию'),
      'options' => array(
        'backup' => t('Скачать архив сайта'),
        ),
      'required' => true,
      )));

    $form->addControl(new SubmitControl(array(
      'text' => t('Продолжить'),
      )));

    return $form->getXML(Control::data(array()));
  }

  public static function on_get(Context $ctx)
  {
    return html::em('content', array(
      'name' => 'form',
      'title' => t('Архивирование сайта'),
      ), self::getAdminFormXML($ctx));
  }
}
