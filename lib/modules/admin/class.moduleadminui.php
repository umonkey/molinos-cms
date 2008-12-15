<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleAdminUI
{
  public function getList(Context $ctx)
  {
    if (is_array($status = $ctx->get('status'))) {
      $message = '<h2>Управление модулями</h2>';

      if (!empty($status['failed'])) {
        $message .= t('<p>Следующие модули не удалось установить: <ul>!list</ul></p><p>Скорее всего возникли проблемы при получении файла. Попробуйте повторить операцию позже.</p>', array(
          '!list' => html::simpleList(explode(',', $status['failed'])),
          ));
      }

      $message .= t('<p><a href="@url">Продолжить</a></p>', array(
        '@url' => '?q=admin/system/modules',
        ));

      return $message;
    }

    $form = self::getSchema()->getForm(array(
      'title' => t('Управление модулями'),
      'action' => '?q=admin.rpc&action=modenable&destination=CURRENT',
      ));

    return $form->getHTML(Control::data(array(
      'modules' => modman::getAllModules(),
      )));
  }

  protected static function getSchema()
  {
    return new Schema(array(
      'modules' => array(
        'type' => 'ModManControl',
        'label' => t('Доступные модули'),
        'columns' => array(
          'check',
          'settings',
          'name',
          'version',
          ),
        ),
      'submit' => array(
        'type' => 'SubmitControl',
        'text' => t('Сохранить изменения'),
        ),
      ));
  }
};
