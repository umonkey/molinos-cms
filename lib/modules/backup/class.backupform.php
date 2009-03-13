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
}
