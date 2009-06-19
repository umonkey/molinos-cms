<?php

class RSSConfig
{
  /**
   * @mcms_message ru.molinos.cms.module.settings.rss
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'feedurl' => array(
        'type' => 'URLControl',
        'label' => t('Адрес основного канала сайта'),
        'description' => t('Будет добавляться во все страницы. Можно указать внешний адрес, если вы используете FeedBurner или что-то аналогичное.'),
        'weight' => 10,
        ),
      'feedname' => array(
        'type' => 'TextLineControl',
        'label' => t('Название основного канала'),
        'weight' => 20,
        ),
      ));
  }
}
