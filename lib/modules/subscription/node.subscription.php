<?php

class SubscriptionNode extends Node
{
  /**
   * Заголовок формы.
   */
  public function formGetTitle()
  {
    return $this->id
      ? t('Подписка для пользователя %name', array('%name' => $this->name))
      : t('Новая подписка на новости');
  }

  /**
   * Возвращает список полей.
   */
  public function getFormfields()
  {
    return new Schema(array(
      'name' => array(
        'type' => 'EmailControl',
        'label' => t('Почтовый адрес'),
        'required' => true,
        ),
      'last' => array(
        'type' => 'NumberControl',
        'label' => t('Последний отправленный объект'),
        'readonly' => true,
        ),
      'sections' => array(
        'type' => 'SectionsControl',
        'label' => t('Подписка распространяется на разделы'),
        'group' => t('Разделы'),
        ),
      ));
  }

  public function getEnabledSections()
  {
    $conf = Context::last()->config->get('modules/subscription');

    if (!array_key_exists('sections', $conf))
      return array();

    return $conf['sections'];
  }

  public function canEditFields()
  {
    return false;
  }

  public function canEditSections()
  {
    return false;
  }

  public function getListURL()
  {
    return 'admin/service/subscription';
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();
    $links['locate'] = null;
    return $links;
  }
}
