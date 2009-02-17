<?php

class OpenIdSettings implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Настройка авторизации'),
      'class' => 'tabbed',
      ));

    $tab = new FieldSetControl(array(
      'name' => 'tab_openid',
      'label' => t('OpenID'),
      ));
    $tab->addControl(new EnumRadioControl(array(
      'value' => 'config_mode',
      'label' => t('Режим работы'),
      'options' => array(
        'open' => t('<strong>С прозрачной регистрацией</strong><br/>Максимальный комфорт для пользователя, регистрироваться в явном виде не нужно.'),
        'closed' => t('<strong>С ручной регистрацией</strong><br/>Использовать <a href=\'@url\'>OpenID</a> для авторизации, но добавлением пользователей в систему занимается администратор сайта.', array('@url' => 'http://ru.wikipedia.org/wiki/OpenID')),
        'none' => t('<strong>Отключить</strong><br/>Не понятно, кому и зачем этот режим может понадобиться, но пусть будет.'),
        ),
      'required' => true,
      'default' => 'open',
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'tab_profilefields',
      'label' => t('Профиль'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'config_profile_fields',
      'label' => t('Запрашиваемые атрибуты'),
      'options' => array(
        'email' => 'Email',
        'fullname' => 'Полное имя',
        'dob' => 'Дата рождения',
        'gender' => 'Пол',
        'postcode' => 'Почтовый индекс',
        'country' => 'Страна',
        'language' => 'Предпочтительный язык',
        'timezone' => 'Часовой пояс',
        ),
      'description' => t('Поля приведены в соответствии со <a href=\'@url\'>спецификацией</a>.', array('@url' => 'http://openid.net/specs/openid-simple-registration-extension-1_0.html#response_format')),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'tab_groups',
      'label' => t('Доступ'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'config_groups_anon',
      'label' => t('Группы для анонимных посетителей'),
      'options' => self::getGroups(),
      )));
    $tab->addControl(new SetControl(array(
      'value' => 'config_groups',
      'label' => t('При регистрации помещать в'),
      'options' => self::getGroups(),
      )));
    $form->addControl($tab);

    return $form;
  }

  private static function getGroups()
  {
    $result = array();

    foreach (Node::find(Context::last()->db, array('class' => 'group')) as $g)
      $result[$g->id] = $g->name;

    asort($result);

    return $result;
  }
}
