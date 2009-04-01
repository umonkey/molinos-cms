<?php

class SearchConfig
{
  /**
   * @mcms_message ru.molinos.cms.admin.menu
   */
  public static function on_poll_menu()
  {
    return array(
      array(
        're' => 'admin/system/settings/search',
        'title' => t('Поиск по сайту'),
        'method' => 'modman::settings',
        ),
      );
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.search
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'engine' => array(
        'type' => 'EnumControl',
        'options' => array(
          'gas' => t('Google Ajax Search'),
          'mg' => t('mnoGoSearch'),
          ),
        'group' => t('Технология поиска'),
        'weight' => 10,
        ),
      'gas_key' => array(
        'type' => 'TextLineControl',
        'label' => t('Ключ Google API'),
        'description' => t('Для работы Google Ajax Search нужно <a href=\'@url\'>получить ключ</a>, уникальный для вашего сайта (это делается бесплатно и быстро).', array('@url' => 'http://code.google.com/apis/ajaxsearch/signup.html')),
        'group' => 'Google Ajax Search',
        'weight' => 20,
        ),
      'mg_dsn' => array(
        'type' => 'TextLineControl',
        'label' => t('Параметры подключения к БД'),
        'description' => t('Строка формата mysql://mnogouser:pass@server/mnogodb/?dbmode=multi'),
        'group' => 'mnoGoSearch',
        'weight' => 30,
        ),
      'mg_ispell' => array(
        'type' => 'TextLineControl',
        'label' => t('Путь к словарям'),
        'description' => t('Введите полный путь к папке ispell.'),
        'group' => 'mnoGoSearch',
        'weight' => 30,
        ),
      'mg_indexer' => array(
        'type' => 'TextLineControl',
        'label' => t('Путь к индексатору'),
        'description' => t('Введите полный путь к исполняемому файлу индексатора (что-то вроде /usr/local/bin/indexer).'),
        'group' => 'mnoGoSearch',
        'weight' => 30,
        ),
      'mg_indexmode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим индексирования'),
        'required' => true,
        'options' => array(
          'web' => t('Обход сайта (медленно)'),
          'db' => t('По базе данных (быстро)'),
          ),
        'group' => 'mnoGoSearch',
        'weight' => 30,
        ),
      'mg_results' => array(
        'type' => 'EnumControl',  
        'label' => t('Страница для результатов'),
        'required' => true,
        'options' => Node::getSortedList('domain'),
        'description' => t('Используется только в режиме индексирования базы данных.  На эту страницу будут вести ссылки, отображаемые в результатах поиска.  При индексировании в режиме обхода сайта этот параметр не используется.'),
        ),
      ));
  }
}
