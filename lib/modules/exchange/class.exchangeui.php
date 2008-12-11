<?php

class ExchangeUI implements iAdminUI
{
  public static function onGet(Context $ctx)
  {
    $form = self::getFormFields($mode = $ctx->get('mode'))->getForm();
    $form->action = '?q=exchange.rpc&action=redirect';
    $form->title = t('Резервное копирование');
    $form->addControl(new SubmitControl(array(
      'text' => t('Продолжить'),
      )));

    return $form->getHTML(Control::data(array()));

    if (null !== ($tmp = $ctx->get('result')))
      return self::getResult($tmp);

    if (null === $ctx->get('mode'))
      return self::getMode($ctx);

    $form = new Form(array(
      'title' => t('Экспорт/импорт сайта в формате XML'),
      'description' => t("Необходимо выбрать совершаемое вами действие"),
      'action' => '?q=exchange.rpc',
      'class' => '',
      'id' => 'mod_exchange'
      ));

    $resstr = array (
      'noprofilename' => 'Ошибка: не введено имя профиля',
      'noimpprofile' => 'Ошибка: не выбран профиль для импорта',
      'notopenr' => 'Ошибка: невозможно открыть файл на чтение',
      'badfiletype' => 'Неподдерживаемый тип файла. Файл должен быть формата XML или ZIP',
      'upgradeok' => 'Upgrade до Mysql прошёл успешно'
      );

    /*
    if ($result)
      $form->addControl(new InfoControl(array('text' => $resstr[$result])));
    */

    $options = array(
       'export' => t('Бэкап'),
       'import' => t('Восстановление'),
       );

    if (Context::last()->db->getDbType() == 'SQLite')
      $options['upgradetoMySQL'] = t('Перенести данные в MySQL');

    $form->addControl(new EnumRadioControl(array(
       'value' => 'exchmode',
       'label' => t('Выберите действие'),
       'default' => 'import',
       'options' => $options
        )));

    $form->addControl(new TextAreaControl(array(
      'value' => 'expprofiledescr',
      'label' => t('Комментарий к бэкапу'),
      )));

    $plist = ExchangeModule::getProfileList();
    $options = array();

    for ($i = 0; $i < count($plist); $i++) {
      $pr = $plist[$i];
      $options[$pr['filename']] = $pr['name'];
    }

    $form->addControl(new AttachmentControl(array(
      'label' => t('Выберите импортируемый профиль'),
      'value' => 'impprofile'
      )));

    if (Context::last()->db->getDbType() == 'SQLite') {
      $form->addControl(new TextLineControl(array(
        'value' => 'db[name]',
        'label' => t('Имя базы данных'),
        'description' => t("Перед инсталляцией база данных будет очищена от существующих данных, сделайте резервную копию!"),
        )));

      $form->addControl(new TextLineControl(array(
        'value' => 'db[host]',
        'label' => t('MySQL сервер'),
        'wrapper_id' => 'db-server',
        'default' => 'localhost',
        )));

      $form->addControl(new TextLineControl(array(
        'value' => 'db[user]',
        'label' => t('Пользователь MySQL'),
        'wrapper_id' => 'db-user',
        )));

      $form->addControl(new PasswordControl(array(
        'value' => 'db[pass]',
        'label' => t('Пароль этого пользователя'),
        'wrapper_id' => 'db-password',
        )));
    }

    $form->addControl(new SubmitControl(array(
      'text' => t('Произвести выбранную операцию'),
       )));

    return $form->getHTML(array());
  }

  public static function getFormFields($mode)
  {
    switch ($mode) {
    default:
      return new Schema(array(
        'mode' => array(
          'type' => 'EnumRadioControl',
          'label' => t('Вы хотите'),
          'options' => array(
            'backup' => t('Создать резервную копию системы'),
            'restore' => t('Восстановить систему из резервной копии'),
            ),
          'required' => true,
          ),
        ));

    case 'restore':
      return new Schema(array(
        'mode' => array(
          'type' => 'HiddenControl',
          'default' => 'restore',
          ),
        'profile' => array(
          'type' => 'EnumControl',
          'label' => t('Выберите импортируемый профиль'),
          'options' => array('manual' => t('Загрузить файл'))
            + ExchangeModule::getProfileList(true),
          'required' => true,
          ),
        'file' => array(
          'type' => 'AttachmentControl',
          'label' => t('Восстановить из резервной копии'),
          'archive' => false,
          'fetch' => false,
          ),
        ));
    }
  }
}
