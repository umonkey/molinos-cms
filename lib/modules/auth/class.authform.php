<?php

class AuthForm
{
  /**
   * Возвращает форму авторизации.
   * 
   * @param Context $ctx 
   * @return string
   * @mcms_message ru.molinos.cms.auth.form
   */
  public static function getXML(Context $ctx)
  {
    if (!($providers = $ctx->registry->poll('ru.molinos.cms.auth.enum')))
      return null;

    $form = self::getForm($providers)->getXML(Control::data());

    return $form;
  }

  private static function getForm(array $list)
  {
    $types = array();

    $form = new Form(array(
      'title' => t('Требуется авторизация'),
      ));

    $class = '';
    foreach ($list as $provider) {
      list($name, $title, $schema) = $provider['result'];

      $types[$name] = $title;

      $tab = $form->addControl(new FieldSetControl(array(
        'name' => $name,
        'id' => $name,
        'label' => $title,
        'class' => 'authmode' . $class,
        )));

      foreach ($schema as $k => $v) {
        $v->group = $title;
        $v->value = $name . '_' . $k;
        $tab->addControl($v);
      }

      $class = ' hidden';
    }

    $form->addControl(new SubmitControl(array(
      'text' => t('Продолжить'),
      )));

    if (1 == count($types)) {
      list($type) = array_keys($types);
      $form->addControl(new HiddenControl(array(
        'value' => 'auth_type',
        'default' => $type,
        )));
    } else {
      $form->addControl(new EnumRadioControl(array(
        'value' => 'auth_type',
        'label' => t('Режим входа'),
        'required' => true,
        'options' => $types,
        )));
    }

    $form->action = '?q=auth.rpc&action=auth&destination=CURRENT';

    return $form;
  }

  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/access',
        'title' => t('Доступ'),
        ),
      array(
        're' => 'admin/access/users',
        'method' => 'on_get_users',
        'title' => t('Пользователи'),
        'description' => t('Управление профилями, принадлежностю к группам, добавление и удаление пользователей.'),
        'sort' => 'auth01',
        ),
      array(
        're' => 'admin/access/groups',
        'method' => 'on_get_groups',
        'title' => t('Группы'),
        'description' => t('Управление правами для отдельных групп.'),
        'sort' => 'auth02',
        ),
      );
  }

  public static function on_get_groups(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('groups');
  }

  public static function on_get_users(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('users');
  }
}
