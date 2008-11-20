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
  public function schema()
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
}
