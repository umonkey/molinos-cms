<?php

class SearchConfig implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());
    $form->addClass('tabbed');

    $tab = new FieldSetControl(array(
      'name' => 'main',
      'label' => t('Режим'),
      ));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_engine',
      'label' => t('Технология поиска'),
      'options' => array(
        'gas' => t('Google Ajax Search'),
        'mg' => t('mnoGoSearch'),
        ),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'gas',
      'label' => t('Google'),
      ));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_gas_key',
      'label' => t('Ключ Google API'),
      'description' => t('Для работы Google Ajax Search нужно <a href=\'@url\'>получить ключ</a>, уникальный для вашего сайта (это делается бесплатно и быстро).', array('@url' => 'http://code.google.com/apis/ajaxsearch/signup.html')),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'mg',
      'label' => t('mnoGoSearch'),
      ));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_mg_dsn',
      'label' => t('Параметры подключения к БД'),
      'description' => t('Строка формата mysql://mnogouser:pass@server/mnogodb/?dbmode=multi'),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_mg_ispell',
      'label' => t('Путь к словарям'),
      'description' => t('Введите полный путь к папке ispell.'),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_mg_indexer',
      'label' => t('Путь к индексатору'),
      'description' => t('Введите полный путь к исполняемому файлу индексатора (что-то вроде /usr/local/bin/indexer).'),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_mg_indexmode',
      'label' => t('Режим индексирования'),
      'required' => true,
      'options' => array(
        'web' => t('Обход сайта (медленно)'),
        'db' => t('По базе данных (быстро)'),
        ),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_mg_results',
      'label' => t('Страница для результатов'),
      'required' => true,
      'options' => DomainNode::getFlatSiteMap('select'),
      'description' => t('Используется только в режиме индексирования базы данных.  На эту страницу будут вести ссылки, отображаемые в результатах поиска.  При индексировании в режиме обхода сайта этот параметр не используется.'),
      )));
    $form->addControl($tab);

    return $form;
  }

  public static function hookPostInstall()
  {
  }
}
