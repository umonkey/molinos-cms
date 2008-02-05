<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(dirname(__FILE__) .'/widget-admin-update.inc');

class UpdateAdminWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Менеджер обновлений',
      'description' => 'Занимается обновлением системы до последней версии..',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['#nocache'] = true;
    $options['phase'] = $ctx->get('phase', 'select');

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['phase']), $options);
  }

  protected function onGetSelect(array $options)
  {
    $html = '<h2>'. t('Обновление Molinos.CMS') .'</h2>';
    $html .= parent::formRender('update-options' /*, array(
      'options' => array('download', 'tables', 'types', 'users', 'ui'),
      )*/);

    return array('html' => $html);
  }

  protected function onGetCurrent(array $options)
  {
    $output = '<h2>'. t('Обновление Molinos.CMS') .'</h2>';
    $output .= '<p>'. t('Обновлений нет.') .'</p>';
    return array('html' => $output);
  }

  protected function onGetSuccess(array $options)
  {
    $output = "<h2>". t("Обновление прошло успешно") ."</h2>";
    $output .= "<p>". t("Текущая версия CMS: %version.", array('%version' => BEBOP_VERSION)) ."</p>";
    $output .= "<p>". t("<a href='@nextlink'>Продолжить работу</a>", array('@nextlink' => '/admin/')) ."</p>";

    return array('html' => $output);
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($id)
  {
    switch ($id) {
    case 'update-options':
      $form = new Form(array());

      $um = new UpdateManager();
      $info = $um->getVersionInfo();

      if ($info['current_build'] == $info['latest_build']) {
        $form->intro = t('Вы используете последнюю версию: %rel.%build.  Информация о новых версиях хранится в кэше, поэтому для принудительной проверки его нужно <a href=\'@link\'>сбросить</a>.', array(
          '%rel' => $info['release'],
          '%build' => $info['current_build'],
          '@link' => '/admin/update/?nocache=1',
          ));

        $form->addControl(new HiddenControl(array(
          'value' => 'force_check',
          )));
        $form->addControl(new SubmitControl(array(
          'text' => t('Проверить обновления'),
          )));
      } else {
        $form->intro = t('Вы используете Molinos.CMS версии %rel.%build, в то время как доступна версия <a href=\'@changelog\'>%rel.%next</a>.  Новые версии обычно содержат исправления обнаруженных проблем, поэтому рекомендуется обновить вашу инсталляцию.', array(
          '%rel' => $info['release'],
          '%build' => $info['current_build'],
          '%next' => $info['latest_build'],
          '@changelog' => 'http://code.google.com/p/molinos-cms/wiki/ChangeLog_'. str_replace('.', '', $info['release']),
          ));

        $form->addControl(new SetControl(array(
          'value' => 'options',
          'options' => array(
            'download' => t('Скачать свежую версию'),
            'tables' => t('Проверить состояние системных таблиц'),
            'types' => t('Проверить встроенные типы'),
            'users' => t('Проверить встроенных пользователей и группы'),
            'ui' => t('Обновить административный интерфейс'),
            ),
          )));
        $form->addControl(new SubmitControl(array(
          'text' => t('Запустить обновление'),
          )));
      }

      return $form;
    }
  }

  public function formGetData($id)
  {
    switch ($id) {
    case 'update-options':
      return array(
        'force_check' => true,
        'options' => array('download', 'tables', 'types', 'users', 'ui'),
        );
    }
  }

  public function formProcess($id, array $data)
  {
    $phase = 'success';

    switch ($id) {
    case 'update-options':
      if (!empty($data['force_check'])) {
        UpdateManager::taskRun();

        $um = new UpdateManager();
        $info = $um->getVersionInfo(true);

        if ($info['latest_build'] > $info['current_build'])
          $phase = null;
        else
          $phase = 'current';
      }

      else {
        if (empty($data['options']))
          $data['options'] = array();

        $um = new UpdateManager();
        $um->runUpdates($data['options']);

        mcms::flush();
      }
    }

    $url = bebop_split_url();
    $url['args'][$this->getInstanceName()] = array(
      'phase' => $phase,
      );

    bebop_redirect($url);
  }
};
