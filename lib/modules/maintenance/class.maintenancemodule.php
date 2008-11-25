<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MaintenanceModule implements iModuleConfig, iRequestHook, iAdminMenu
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Управление техническими работами'),
      ));

    $form->addControl(new EnumRadioControl(array(
      'value' => 'config_state',
      'label' => t('Текущее состояние'),
      'options' => array(
        '' => t('Сайт работает'),
        'closed' => t('Ведутся технические работы'),
        ),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookRequest(Context $ctx = null)
  {
    if (null === $ctx) {
      $conf = mcms::modconf('maintenance');

      if (!empty($conf['state']) and 'closed' === $conf['state']) {
        $url = bebop_split_url();

        if ('admin' != substr($url['path'], 0, 7)) {
          $r = new Response(t('На сервере ведутся технические работы, обратитесь чуть позже.'), 'text/plain', 503);
          $r->send();
        }
      }
    }
  }

  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasAccess('u', 'domain'))
      $icons[] = array(
        'group' => 'Разработка',
        'img' => 'img/cms-maintenance.png',
        'href' => 'admin?mode=modules&action=config&name=maintenance&destination=CURRENT',
        'title' => t('Технические работы'),
        'description' => t('Позволяет временно закрыть сайт для проведения технических работ.'),
        );

    return $icons;
  }
}
