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
    if (!($providers = $ctx->registry->enum('ru.molinos.cms.auth.enum')))
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
        'deault' => $type,
        )));
    } else {
      $typesctl = $form->addControl(new EnumRadioControl(array(
        'value' => 'auth_type',
        'label' => t('Режим входа'),
        'required' => true,
        'options' => $types,
        )));
    }

    $form->action = '?q=auth.rpc&action=auth&destination=CURRENT';

    return $form;
  }
}
