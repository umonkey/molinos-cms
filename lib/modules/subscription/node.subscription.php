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
   * Схема фиксирована, изменение невозможно.
   */
  public static function getDefaultSchema()
  {
    return array(
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
      );
  }

  public function getEnabledSections()
  {
    $conf = mcms::modconf('subscription');

    if (!array_key_exists('sections', $conf))
      return array();

    if (array_key_exists('__reset', $conf['sections']))
      unset($conf['sections']['__reset']);

    return $conf['sections'];
  }
}
