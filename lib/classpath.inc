<?php return array (
  'modules' => 
  array (
    'accesslog' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Statistics',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Content Access Statistics.',
        'ru' => 'Статистика доступа к контенту.',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_accesslog',
    ),
    'admin' => 
    array (
      'classes' => 
      array (
        0 => 'AdminListHandler',
        1 => 'AdminMenu',
        2 => 'AdminRPC',
        3 => 'AdminStatus',
        4 => 'AdminTreeHandler',
        5 => 'ModuleAdminUI',
        6 => 'NodeIndexer',
        7 => 'AdminUIListControl',
        8 => 'AdminUINodeActionsControl',
        9 => 'AdminUISearchControl',
        10 => 'AdminUITreeControl',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminMenu',
        1 => 'iRemoteCall',
        2 => 'iFormControl',
      ),
      'implementors' => 
      array (
        'iAdminMenu' => 
        array (
          0 => 'AdminMenu',
          1 => 'AdminStatus',
        ),
        'iRemoteCall' => 
        array (
          0 => 'AdminRPC',
        ),
        'iFormControl' => 
        array (
          0 => 'AdminUIListControl',
          1 => 'AdminUINodeActionsControl',
          2 => 'AdminUISearchControl',
          3 => 'AdminUITreeControl',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Molinos.CMS Administragive UI',
        'ru' => 'Интерфейс администрирования Molinos.CMS',
      ),
    ),
    'adminext' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Interactive',
      'version' => '8.05.6192',
      'name' => 
      array (
        'en' => 'Administrative Extensions',
        'ru' => 'Административные расширения',
      ),
    ),
    'attachment' => 
    array (
      'classes' => 
      array (
        0 => 'Attachment',
        1 => 'AttachmentModule',
        2 => 'AttachmentControl',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iFormControl',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'AttachmentModule',
        ),
        'iFormControl' => 
        array (
          0 => 'AttachmentControl',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'name' => 
      array (
        'en' => 'File Download Service',
        'ru' => 'Доступ к файловому архиву',
      ),
    ),
    'auth' => 
    array (
      'classes' => 
      array (
        0 => 'User',
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Authentication Subsystem',
        'ru' => 'Система авторизации',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_auth',
    ),
    'autocomplete' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'File/Node Autocomplete',
        'ru' => 'Автоматический подбор файлов и документов',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_autocomplete',
    ),
    'base' => 
    array (
      'classes' => 
      array (
        0 => 'BaseCron',
        1 => 'BaseModule',
        2 => 'BebopCache',
        3 => 'Calendar',
        4 => 'Config',
        5 => 'Context',
        6 => 'Control',
        7 => 'Form',
        8 => 'Installer',
        9 => 'mcms',
        10 => 'Node',
        11 => 'NodeBase',
        12 => 'NodeMover',
        13 => 'NodeQueryBuilder',
        14 => 'Page',
        15 => 'Schema',
        16 => 'Session',
        17 => 'Structure',
        18 => 'StructureMA',
        19 => 'url',
        20 => 'Widget',
        21 => 'AccessControl',
        22 => 'AccessRevControl',
        23 => 'ActionsControl',
        24 => 'BoolControl',
        25 => 'DateControl',
        26 => 'DateTimeControl',
        27 => 'EmailControl',
        28 => 'EnumControl',
        29 => 'EnumRadioControl',
        30 => 'FieldControl',
        31 => 'FieldSetControl',
        32 => 'FloatControl',
        33 => 'HiddenControl',
        34 => 'HistoryControl',
        35 => 'InfoControl',
        36 => 'ListControl',
        37 => 'NodeLinkControl',
        38 => 'NumberControl',
        39 => 'PagerControl',
        40 => 'PasswordControl',
        41 => 'ResetControl',
        42 => 'SectionControl',
        43 => 'SectionsControl',
        44 => 'SetControl',
        45 => 'SubmitControl',
        46 => 'TableControl',
        47 => 'TextAreaControl',
        48 => 'TextHTMLControl',
        49 => 'TextLineControl',
        50 => 'URLControl',
        51 => 'BadRequestException',
        52 => 'DuplicateException',
        53 => 'ForbiddenException',
        54 => 'NoIndexException',
        55 => 'NotInstalledException',
        56 => 'ObjectNotFoundException',
        57 => 'PageNotFoundException',
        58 => 'UnauthorizedException',
        59 => 'UserErrorException',
        60 => 'ValidationException',
        61 => 'WidgetHaltedException',
        62 => 'DomainNode',
        63 => 'FileNode',
        64 => 'GroupNode',
        65 => 'TagNode',
        66 => 'TypeNode',
        67 => 'UserNode',
        68 => 'WidgetNode',
        69 => 'ArchiveWidget',
        70 => 'DocWidget',
        71 => 'FormWidget',
        72 => 'LinkedNodesWidget',
        73 => 'ListWidget',
        74 => 'MenuWidget',
        75 => 'TagCloudWidget',
        76 => 'TagsWidget',
        77 => 'TitleWidget',
        78 => 'UserWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iScheduler',
        1 => 'iRemoteCall',
        2 => 'iModuleConfig',
        3 => 'iNodeHook',
        4 => 'iFormControl',
        5 => 'iContentType',
        6 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iScheduler' => 
        array (
          0 => 'BaseCron',
          1 => 'TypeNode',
        ),
        'iRemoteCall' => 
        array (
          0 => 'BaseModule',
        ),
        'iModuleConfig' => 
        array (
          0 => 'BaseModule',
          1 => 'TypeNode',
        ),
        'iNodeHook' => 
        array (
          0 => 'BaseModule',
        ),
        'iFormControl' => 
        array (
          0 => 'Control',
          1 => 'Form',
          2 => 'AccessControl',
          3 => 'AccessRevControl',
          4 => 'ActionsControl',
          5 => 'BoolControl',
          6 => 'DateControl',
          7 => 'DateTimeControl',
          8 => 'EmailControl',
          9 => 'EnumControl',
          10 => 'EnumRadioControl',
          11 => 'FieldControl',
          12 => 'FieldSetControl',
          13 => 'FloatControl',
          14 => 'HiddenControl',
          15 => 'HistoryControl',
          16 => 'InfoControl',
          17 => 'ListControl',
          18 => 'NodeLinkControl',
          19 => 'NumberControl',
          20 => 'PagerControl',
          21 => 'PasswordControl',
          22 => 'ResetControl',
          23 => 'SectionControl',
          24 => 'SectionsControl',
          25 => 'SetControl',
          26 => 'SubmitControl',
          27 => 'TableControl',
          28 => 'TextAreaControl',
          29 => 'TextHTMLControl',
          30 => 'TextLineControl',
          31 => 'URLControl',
        ),
        'iContentType' => 
        array (
          0 => 'Node',
          1 => 'Node',
          2 => 'DomainNode',
          3 => 'DomainNode',
          4 => 'FileNode',
          5 => 'FileNode',
          6 => 'GroupNode',
          7 => 'GroupNode',
          8 => 'TagNode',
          9 => 'TagNode',
          10 => 'TypeNode',
          11 => 'TypeNode',
          12 => 'UserNode',
          13 => 'UserNode',
          14 => 'WidgetNode',
          15 => 'WidgetNode',
        ),
        'iWidget' => 
        array (
          0 => 'Widget',
          1 => 'ArchiveWidget',
          2 => 'ArchiveWidget',
          3 => 'DocWidget',
          4 => 'DocWidget',
          5 => 'FormWidget',
          6 => 'LinkedNodesWidget',
          7 => 'ListWidget',
          8 => 'MenuWidget',
          9 => 'MenuWidget',
          10 => 'TagCloudWidget',
          11 => 'TagCloudWidget',
          12 => 'TagsWidget',
          13 => 'TagsWidget',
          14 => 'TitleWidget',
          15 => 'UserWidget',
          16 => 'UserWidget',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Molinos.CMS Base',
        'ru' => 'Базовые классы Molinos.CMS',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_base',
    ),
    'blog' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'User Blogs',
        'ru' => 'Блоги пользователей',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_blog',
    ),
    'captcha' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.5',
      'name' => 
      array (
        'en' => 'Captcha module',
        'ru' => 'Предотвращение автоматизированного заполнения форм.',
      ),
    ),
    'cart' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Simple Shopping Cart',
        'ru' => 'Простая корзина для заказов',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_cart',
    ),
    'comment' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Interactive',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Basic Comment Support',
        'ru' => 'Базовая поддержка комментариев',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_comment',
    ),
    'compressor' => 
    array (
      'classes' => 
      array (
        0 => 'CompressorModule',
        1 => 'CompressorSettings',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iModuleConfig',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'CompressorModule',
        ),
        'iModuleConfig' => 
        array (
          0 => 'CompressorSettings',
        ),
      ),
      'enabled' => true,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Extras Compressor (JS, CSS, HTML)',
        'ru' => 'Компрессор JS, CSS и HTML',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_compressor',
    ),
    'cron' => 
    array (
      'classes' => 
      array (
        0 => 'CronMenu',
        1 => 'CronModule',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminMenu',
        1 => 'iModuleConfig',
        2 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iAdminMenu' => 
        array (
          0 => 'CronMenu',
        ),
        'iModuleConfig' => 
        array (
          0 => 'CronModule',
        ),
        'iRemoteCall' => 
        array (
          0 => 'CronModule',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Task Scheduling',
        'ru' => 'Выполнение периодических задач',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_cron',
    ),
    'drawtext' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Text Rasterization Module',
        'ru' => 'Растеризация текста',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_drawtext',
    ),
    'exchange' => 
    array (
      'classes' => 
      array (
        0 => 'ExchangeModule',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iAdminMenu',
        2 => 'iAdminUI',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'ExchangeModule',
        ),
        'iAdminMenu' => 
        array (
          0 => 'ExchangeModule',
        ),
        'iAdminUI' => 
        array (
          0 => 'ExchangeModule',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '1.0',
      'name' => 
      array (
        'en' => 'Database Import and Export',
        'ru' => 'Импорт и экспорт сайта',
      ),
    ),
    'getid3' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'name' => 
      array (
        'en' => 'ID3 Tag Library',
        'ru' => 'Доступ к метаданным файлов (mp3, swf итд).',
      ),
      'docurl' => 'http://getid3.sourceforge.net/',
    ),
    'googleanalytics' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Statistics',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Google Analytics',
        'ru' => 'Google Analytics',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_googleanalytics',
    ),
    'imgtoolkit' => 
    array (
      'classes' => 
      array (
        0 => 'ImageEdit',
        1 => 'ImageMagick',
        2 => 'ImageMagickGD',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'ImageEdit',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Image Processing',
        'ru' => 'Обработка изображений',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_imgtoolkit',
    ),
    'install' => 
    array (
      'classes' => 
      array (
        0 => 'InstallModule',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'InstallModule',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'name' => 
      array (
        'en' => 'Molinos.CMS Installer',
        'ru' => 'Установщик Molinos.CMS',
      ),
    ),
    'maintenance' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Disables the Web Site for Maintenance',
        'ru' => 'Отключение сайта для технических работ',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_maintenance',
    ),
    'mimemail' => 
    array (
      'classes' => 
      array (
        0 => 'BebopMimeMail',
        1 => 'htmlMimeMail',
        2 => 'Mail_mimePart',
        3 => 'Mail_RFC822',
        4 => 'smtp{',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'BebopMimeMail',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'HTML Mail Sender',
        'ru' => 'Отправка почты в HTML',
      ),
    ),
    'moderator' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Moderation Queue',
        'ru' => 'Очередь модерирования',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_moderator',
    ),
    'msg' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'name' => 
      array (
        'en' => 'Internal Messaging Subsystem',
        'ru' => 'Система внутренних сообщений',
      ),
    ),
    'nodeapi' => 
    array (
      'classes' => 
      array (
        0 => 'NodeApiModule',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'NodeApiModule',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Node API',
        'ru' => 'API для работы с документами',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_nodeapi',
    ),
    'openid' => 
    array (
      'classes' => 
      array (
        0 => 'OpenIdModule',
        1 => 'OpenIdSettings',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iModuleConfig',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'OpenIdModule',
        ),
        'iModuleConfig' => 
        array (
          0 => 'OpenIdSettings',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '1.0',
      'name' => 
      array (
        'en' => 'OpenID',
        'ru' => 'работа с OpenID',
      ),
    ),
    'pdo' => 
    array (
      'classes' => 
      array (
        0 => 'DBSchema_node',
        1 => 'DBSchema_node__access',
        2 => 'DBSchema_node__fallback',
        3 => 'DBSchema_node__log',
        4 => 'DBSchema_node__poll',
        5 => 'DBSchema_node__rel',
        6 => 'DBSchema_node__rev',
        7 => 'DBSchema_node__seq',
        8 => 'DBSchema_node__session',
        9 => 'mcms_mysql_driver',
        10 => 'mcms_sqlite_driver',
        11 => 'PDO_Singleton',
        12 => 'TableInfo',
        13 => 'TableManager',
        14 => 'McmsPDOException',
        15 => 'ReadOnlyDatabaseException',
        16 => 'TableNotFoundException',
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'DB Access',
        'ru' => 'Доступ к БД',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_pdo',
    ),
    'playlist' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'XSPF Playlist Generator',
        'ru' => 'Генератор XSPF плэйлистов',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_playlist',
    ),
    'poll' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Interactive',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Content Poll',
        'ru' => 'Опрос пользователей',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_poll',
    ),
    'rating' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Interactive',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Content Rating',
        'ru' => 'Оценка документов',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_rating',
    ),
    'robots' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Control over Web Crawlers',
        'ru' => 'Управление веб-роботами',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_robots',
    ),
    'rss' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'name' => 
      array (
        'en' => 'RSS Exporter',
        'ru' => 'Экспорт RSS',
      ),
    ),
    'search' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Interactive',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Site-Wide Search',
        'ru' => 'Поиск по сайту',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_search',
    ),
    'sitemap' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'name' => 
      array (
        'en' => 'Sitemap',
        'ru' => 'Sitemap',
      ),
      'docurl' => 'http://www.sitemaps.org/ru/protocol.php',
    ),
    'smarty' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Smarty Template Engine',
        'ru' => 'Поддержка шаблонов на Smarty',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_smarty',
    ),
    'subscription' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Interactive',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Email Subscription',
        'ru' => 'Рассылка новостей по почте',
      ),
    ),
    'syslog' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Statistics',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'System Log',
        'ru' => 'Системный журнал',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_syslog',
    ),
    'tinymce' => 
    array (
      'classes' => 
      array (
        0 => 'TinyMceModule',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iPageHook',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'TinyMceModule',
        ),
        'iPageHook' => 
        array (
          0 => 'TinyMceModule',
        ),
      ),
      'enabled' => true,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Internal WYSIWYG Editor',
        'ru' => 'Текстовый редактор TinyMCE',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_tinymce',
    ),
    'todo' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'name' => 
      array (
        'en' => 'Todo Manager',
        'ru' => 'Управление задачами',
      ),
    ),
    'update' => 
    array (
      'classes' => 
      array (
        0 => 'UpdateMenu',
        1 => 'Updater',
        2 => 'UpdateScheduler',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminMenu',
        1 => 'iAdminUI',
        2 => 'iRemoteCall',
        3 => 'iScheduler',
      ),
      'implementors' => 
      array (
        'iAdminMenu' => 
        array (
          0 => 'UpdateMenu',
        ),
        'iAdminUI' => 
        array (
          0 => 'Updater',
        ),
        'iRemoteCall' => 
        array (
          0 => 'Updater',
        ),
        'iScheduler' => 
        array (
          0 => 'UpdateScheduler',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'name' => 
      array (
        'en' => 'Automatic Updates',
        'ru' => 'Автоматическое обновление',
      ),
    ),
    'xmlimport' => 
    array (
      'classes' => 
      array (
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => false,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'XmlImporter Widget',
        'ru' => 'Виджет XmlImporter',
      ),
    ),
  ),
  'classes' => 
  array (
    'accesscontrol' => 'lib/modules/base/control.access.php',
    'accessrevcontrol' => 'lib/modules/base/control.accessrev.php',
    'actionscontrol' => 'lib/modules/base/control.actions.php',
    'adminlisthandler' => 'lib/modules/admin/class.adminlisthandler.php',
    'adminmenu' => 'lib/modules/admin/class.adminmenu.php',
    'adminrpc' => 'lib/modules/admin/class.adminrpc.php',
    'adminstatus' => 'lib/modules/admin/class.adminstatus.php',
    'admintreehandler' => 'lib/modules/admin/class.admintreehandler.php',
    'adminuilistcontrol' => 'lib/modules/admin/control.adminuilist.php',
    'adminuinodeactionscontrol' => 'lib/modules/admin/control.adminuinodeactions.php',
    'adminuisearchcontrol' => 'lib/modules/admin/control.adminuisearch.php',
    'adminuitreecontrol' => 'lib/modules/admin/control.adminuitree.php',
    'archivewidget' => 'lib/modules/base/widget.archive.php',
    'attachment' => 'lib/modules/attachment/class.attachment.php',
    'attachmentcontrol' => 'lib/modules/attachment/control.attachment.php',
    'attachmentmodule' => 'lib/modules/attachment/class.attachmentmodule.php',
    'badrequestexception' => 'lib/modules/base/exception.badrequest.php',
    'basecron' => 'lib/modules/base/class.basecron.php',
    'basemodule' => 'lib/modules/base/class.basemodule.php',
    'bebopcache' => 'lib/modules/base/class.bebopcache.php',
    'bebopmimemail' => 'lib/modules/mimemail/class.bebopmimemail.php',
    'boolcontrol' => 'lib/modules/base/control.bool.php',
    'calendar' => 'lib/modules/base/class.calendar.php',
    'compressormodule' => 'lib/modules/compressor/class.compressormodule.php',
    'compressorsettings' => 'lib/modules/compressor/class.compressorsettings.php',
    'config' => 'lib/modules/base/class.config.php',
    'context' => 'lib/modules/base/class.context.php',
    'control' => 'lib/modules/base/class.control.php',
    'cronmenu' => 'lib/modules/cron/class.cronmenu.php',
    'cronmodule' => 'lib/modules/cron/class.cronmodule.php',
    'datecontrol' => 'lib/modules/base/control.date.php',
    'datetimecontrol' => 'lib/modules/base/control.datetime.php',
    'dbschema_node' => 'lib/modules/pdo/class.dbschema_node.php',
    'dbschema_node__access' => 'lib/modules/pdo/class.dbschema_node__access.php',
    'dbschema_node__fallback' => 'lib/modules/pdo/class.dbschema_node__fallback.php',
    'dbschema_node__log' => 'lib/modules/pdo/class.dbschema_node__log.php',
    'dbschema_node__poll' => 'lib/modules/pdo/class.dbschema_node__poll.php',
    'dbschema_node__rel' => 'lib/modules/pdo/class.dbschema_node__rel.php',
    'dbschema_node__rev' => 'lib/modules/pdo/class.dbschema_node__rev.php',
    'dbschema_node__seq' => 'lib/modules/pdo/class.dbschema_node__seq.php',
    'dbschema_node__session' => 'lib/modules/pdo/class.dbschema_node__session.php',
    'docwidget' => 'lib/modules/base/widget.doc.php',
    'domainnode' => 'lib/modules/base/node.domain.php',
    'duplicateexception' => 'lib/modules/base/exception.duplicate.php',
    'emailcontrol' => 'lib/modules/base/control.email.php',
    'enumcontrol' => 'lib/modules/base/control.enum.php',
    'enumradiocontrol' => 'lib/modules/base/control.enumradio.php',
    'exchangemodule' => 'lib/modules/exchange/class.exchangemodule.php',
    'fieldcontrol' => 'lib/modules/base/control.field.php',
    'fieldsetcontrol' => 'lib/modules/base/control.fieldset.php',
    'filenode' => 'lib/modules/base/node.file.php',
    'floatcontrol' => 'lib/modules/base/control.float.php',
    'forbiddenexception' => 'lib/modules/base/exception.forbidden.php',
    'form' => 'lib/modules/base/class.form.php',
    'formwidget' => 'lib/modules/base/widget.form.php',
    'groupnode' => 'lib/modules/base/node.group.php',
    'hiddencontrol' => 'lib/modules/base/control.hidden.php',
    'historycontrol' => 'lib/modules/base/control.history.php',
    'htmlmimemail' => 'lib/modules/mimemail/class.htmlmimemail.php',
    'iadminmenu' => 'lib/modules/base/interface.adminmenu.php',
    'iadminui' => 'lib/modules/admin/interface.adminui.php',
    'icontenttype' => 'lib/modules/base/interface.contenttype.php',
    'iformcontrol' => 'lib/modules/base/interface.formcontrol.php',
    'imageedit' => 'lib/modules/imgtoolkit/class.imageedit.php',
    'imagemagick' => 'lib/modules/imgtoolkit/class.imagemagick.php',
    'imagemagickgd' => 'lib/modules/imgtoolkit/class.imagemagickgd.php',
    'imoduleconfig' => 'lib/modules/base/interface.moduleconfig.php',
    'infocontrol' => 'lib/modules/base/control.info.php',
    'inodehook' => 'lib/modules/base/interface.nodehook.php',
    'installer' => 'lib/modules/base/class.installer.php',
    'installmodule' => 'lib/modules/install/class.installmodule.php',
    'ipagehook' => 'lib/modules/base/interface.pagehook.php',
    'iremotecall' => 'lib/modules/base/interface.remotecall.php',
    'irequesthook' => 'lib/modules/base/interface.requesthook.php',
    'ischeduler' => 'lib/modules/base/interface.scheduler.php',
    'iwidget' => 'lib/modules/base/interface.widget.php',
    'linkednodeswidget' => 'lib/modules/base/widget.linkednodes.php',
    'listcontrol' => 'lib/modules/base/control.list.php',
    'listwidget' => 'lib/modules/base/widget.list.php',
    'mail_mimepart' => 'lib/modules/mimemail/class.mail_mimepart.php',
    'mail_rfc822' => 'lib/modules/mimemail/class.mail_rfc822.php',
    'mcms' => 'lib/modules/base/class.mcms.php',
    'mcms_mysql_driver' => 'lib/modules/pdo/class.mcms_mysql_driver.php',
    'mcms_sqlite_driver' => 'lib/modules/pdo/class.mcms_sqlite_driver.php',
    'mcmspdoexception' => 'lib/modules/pdo/exception.mcmspdo.php',
    'menuwidget' => 'lib/modules/base/widget.menu.php',
    'moduleadminui' => 'lib/modules/admin/class.moduleadminui.php',
    'node' => 'lib/modules/base/class.node.php',
    'nodeapimodule' => 'lib/modules/nodeapi/class.nodeapimodule.php',
    'nodebase' => 'lib/modules/base/class.nodebase.php',
    'nodeindexer' => 'lib/modules/admin/class.nodeindexer.php',
    'nodelinkcontrol' => 'lib/modules/base/control.nodelink.php',
    'nodemover' => 'lib/modules/base/class.nodemover.php',
    'nodequerybuilder' => 'lib/modules/base/class.nodequerybuilder.php',
    'noindexexception' => 'lib/modules/base/exception.noindex.php',
    'notinstalledexception' => 'lib/modules/base/exception.notinstalled.php',
    'numbercontrol' => 'lib/modules/base/control.number.php',
    'objectnotfoundexception' => 'lib/modules/base/exception.objectnotfound.php',
    'openidmodule' => 'lib/modules/openid/class.openidmodule.php',
    'openidsettings' => 'lib/modules/openid/class.openidsettings.php',
    'page' => 'lib/modules/base/class.page.php',
    'pagenotfoundexception' => 'lib/modules/base/exception.pagenotfound.php',
    'pagercontrol' => 'lib/modules/base/control.pager.php',
    'passwordcontrol' => 'lib/modules/base/control.password.php',
    'pdo_singleton' => 'lib/modules/pdo/class.pdo_singleton.php',
    'readonlydatabaseexception' => 'lib/modules/pdo/exception.readonlydatabase.php',
    'resetcontrol' => 'lib/modules/base/control.reset.php',
    'schema' => 'lib/modules/base/class.schema.php',
    'sectioncontrol' => 'lib/modules/base/control.section.php',
    'sectionscontrol' => 'lib/modules/base/control.sections.php',
    'session' => 'lib/modules/base/class.session.php',
    'setcontrol' => 'lib/modules/base/control.set.php',
    'smtp' => 'lib/modules/mimemail/class.smtp.php',
    'structure' => 'lib/modules/base/class.structure.php',
    'structurema' => 'lib/modules/base/class.structurema.php',
    'submitcontrol' => 'lib/modules/base/control.submit.php',
    'tablecontrol' => 'lib/modules/base/control.table.php',
    'tableinfo' => 'lib/modules/pdo/class.tableinfo.php',
    'tablemanager' => 'lib/modules/pdo/class.tablemanager.php',
    'tablenotfoundexception' => 'lib/modules/pdo/exception.tablenotfound.php',
    'tagcloudwidget' => 'lib/modules/base/widget.tagcloud.php',
    'tagnode' => 'lib/modules/base/node.tag.php',
    'tagswidget' => 'lib/modules/base/widget.tags.php',
    'textareacontrol' => 'lib/modules/base/control.textarea.php',
    'texthtmlcontrol' => 'lib/modules/base/control.texthtml.php',
    'textlinecontrol' => 'lib/modules/base/control.textline.php',
    'tinymcemodule' => 'lib/modules/tinymce/class.tinymcemodule.php',
    'titlewidget' => 'lib/modules/base/widget.title.php',
    'typenode' => 'lib/modules/base/node.type.php',
    'unauthorizedexception' => 'lib/modules/base/exception.unauthorized.php',
    'updatemenu' => 'lib/modules/update/class.updatemenu.php',
    'updater' => 'lib/modules/update/class.updater.php',
    'updatescheduler' => 'lib/modules/update/class.updatescheduler.php',
    'url' => 'lib/modules/base/class.url.php',
    'urlcontrol' => 'lib/modules/base/control.url.php',
    'user' => 'lib/modules/auth/class.user.php',
    'usererrorexception' => 'lib/modules/base/exception.usererror.php',
    'usernode' => 'lib/modules/base/node.user.php',
    'userwidget' => 'lib/modules/base/widget.user.php',
    'validationexception' => 'lib/modules/base/exception.validation.php',
    'widget' => 'lib/modules/base/class.widget.php',
    'widgethaltedexception' => 'lib/modules/base/exception.widgethalted.php',
    'widgetnode' => 'lib/modules/base/node.widget.php',
  ),
  'interfaces' => 
  array (
    'iadminmenu' => 
    array (
      0 => 'adminmenu',
      1 => 'adminstatus',
      2 => 'cronmenu',
      3 => 'exchangemodule',
      4 => 'updatemenu',
    ),
    'iadminui' => 
    array (
      0 => 'exchangemodule',
      1 => 'updater',
    ),
    'icontenttype' => 
    array (
      0 => 'domainnode',
      1 => 'filenode',
      2 => 'groupnode',
      3 => 'node',
      4 => 'tagnode',
      5 => 'typenode',
      6 => 'usernode',
      7 => 'widgetnode',
    ),
    'iformcontrol' => 
    array (
      0 => 'accesscontrol',
      1 => 'accessrevcontrol',
      2 => 'actionscontrol',
      3 => 'adminuilistcontrol',
      4 => 'adminuinodeactionscontrol',
      5 => 'adminuisearchcontrol',
      6 => 'adminuitreecontrol',
      7 => 'attachmentcontrol',
      8 => 'boolcontrol',
      9 => 'control',
      10 => 'datecontrol',
      11 => 'datetimecontrol',
      12 => 'emailcontrol',
      13 => 'enumcontrol',
      14 => 'enumradiocontrol',
      15 => 'fieldcontrol',
      16 => 'fieldsetcontrol',
      17 => 'floatcontrol',
      18 => 'form',
      19 => 'hiddencontrol',
      20 => 'historycontrol',
      21 => 'infocontrol',
      22 => 'listcontrol',
      23 => 'nodelinkcontrol',
      24 => 'numbercontrol',
      25 => 'pagercontrol',
      26 => 'passwordcontrol',
      27 => 'resetcontrol',
      28 => 'sectioncontrol',
      29 => 'sectionscontrol',
      30 => 'setcontrol',
      31 => 'submitcontrol',
      32 => 'tablecontrol',
      33 => 'textareacontrol',
      34 => 'texthtmlcontrol',
      35 => 'textlinecontrol',
      36 => 'urlcontrol',
    ),
    'imoduleconfig' => 
    array (
      0 => 'basemodule',
      1 => 'bebopmimemail',
      2 => 'compressorsettings',
      3 => 'cronmodule',
      4 => 'openidsettings',
      5 => 'tinymcemodule',
      6 => 'typenode',
    ),
    'inodehook' => 
    array (
      0 => 'basemodule',
    ),
    'ipagehook' => 
    array (
      0 => 'tinymcemodule',
    ),
    'iremotecall' => 
    array (
      0 => 'adminrpc',
      1 => 'attachmentmodule',
      2 => 'basemodule',
      3 => 'compressormodule',
      4 => 'cronmodule',
      5 => 'exchangemodule',
      6 => 'imageedit',
      7 => 'installmodule',
      8 => 'nodeapimodule',
      9 => 'openidmodule',
      10 => 'updater',
    ),
    'ischeduler' => 
    array (
      0 => 'basecron',
      1 => 'typenode',
      2 => 'updatescheduler',
    ),
    'iwidget' => 
    array (
      0 => 'archivewidget',
      1 => 'docwidget',
      2 => 'formwidget',
      3 => 'linkednodeswidget',
      4 => 'listwidget',
      5 => 'menuwidget',
      6 => 'tagcloudwidget',
      7 => 'tagswidget',
      8 => 'titlewidget',
      9 => 'userwidget',
      10 => 'widget',
    ),
  ),
  'rclasses' => 
  array (
    'adminlisthandler' => 'admin',
    'adminmenu' => 'admin',
    'adminrpc' => 'admin',
    'adminstatus' => 'admin',
    'admintreehandler' => 'admin',
    'moduleadminui' => 'admin',
    'nodeindexer' => 'admin',
    'adminuilistcontrol' => 'admin',
    'adminuinodeactionscontrol' => 'admin',
    'adminuisearchcontrol' => 'admin',
    'adminuitreecontrol' => 'admin',
    'iadminui' => 'admin',
    'attachment' => 'attachment',
    'attachmentmodule' => 'attachment',
    'attachmentcontrol' => 'attachment',
    'user' => 'auth',
    'basecron' => 'base',
    'basemodule' => 'base',
    'bebopcache' => 'base',
    'calendar' => 'base',
    'config' => 'base',
    'context' => 'base',
    'control' => 'base',
    'form' => 'base',
    'installer' => 'base',
    'mcms' => 'base',
    'node' => 'base',
    'nodebase' => 'base',
    'nodemover' => 'base',
    'nodequerybuilder' => 'base',
    'page' => 'base',
    'schema' => 'base',
    'session' => 'base',
    'structure' => 'base',
    'structurema' => 'base',
    'url' => 'base',
    'widget' => 'base',
    'accesscontrol' => 'base',
    'accessrevcontrol' => 'base',
    'actionscontrol' => 'base',
    'boolcontrol' => 'base',
    'datecontrol' => 'base',
    'datetimecontrol' => 'base',
    'emailcontrol' => 'base',
    'enumcontrol' => 'base',
    'enumradiocontrol' => 'base',
    'fieldcontrol' => 'base',
    'fieldsetcontrol' => 'base',
    'floatcontrol' => 'base',
    'hiddencontrol' => 'base',
    'historycontrol' => 'base',
    'infocontrol' => 'base',
    'listcontrol' => 'base',
    'nodelinkcontrol' => 'base',
    'numbercontrol' => 'base',
    'pagercontrol' => 'base',
    'passwordcontrol' => 'base',
    'resetcontrol' => 'base',
    'sectioncontrol' => 'base',
    'sectionscontrol' => 'base',
    'setcontrol' => 'base',
    'submitcontrol' => 'base',
    'tablecontrol' => 'base',
    'textareacontrol' => 'base',
    'texthtmlcontrol' => 'base',
    'textlinecontrol' => 'base',
    'urlcontrol' => 'base',
    'badrequestexception' => 'base',
    'duplicateexception' => 'base',
    'forbiddenexception' => 'base',
    'noindexexception' => 'base',
    'notinstalledexception' => 'base',
    'objectnotfoundexception' => 'base',
    'pagenotfoundexception' => 'base',
    'unauthorizedexception' => 'base',
    'usererrorexception' => 'base',
    'validationexception' => 'base',
    'widgethaltedexception' => 'base',
    'iadminmenu' => 'base',
    'icontenttype' => 'base',
    'iformcontrol' => 'base',
    'imoduleconfig' => 'base',
    'inodehook' => 'base',
    'ipagehook' => 'base',
    'iremotecall' => 'base',
    'irequesthook' => 'base',
    'ischeduler' => 'base',
    'iwidget' => 'base',
    'domainnode' => 'base',
    'filenode' => 'base',
    'groupnode' => 'base',
    'tagnode' => 'base',
    'typenode' => 'base',
    'usernode' => 'base',
    'widgetnode' => 'base',
    'archivewidget' => 'base',
    'docwidget' => 'base',
    'formwidget' => 'base',
    'linkednodeswidget' => 'base',
    'listwidget' => 'base',
    'menuwidget' => 'base',
    'tagcloudwidget' => 'base',
    'tagswidget' => 'base',
    'titlewidget' => 'base',
    'userwidget' => 'base',
    'compressormodule' => 'compressor',
    'compressorsettings' => 'compressor',
    'cronmenu' => 'cron',
    'cronmodule' => 'cron',
    'exchangemodule' => 'exchange',
    'imageedit' => 'imgtoolkit',
    'imagemagick' => 'imgtoolkit',
    'imagemagickgd' => 'imgtoolkit',
    'installmodule' => 'install',
    'bebopmimemail' => 'mimemail',
    'htmlmimemail' => 'mimemail',
    'mail_mimepart' => 'mimemail',
    'mail_rfc822' => 'mimemail',
    'smtp' => 'mimemail',
    'nodeapimodule' => 'nodeapi',
    'openidmodule' => 'openid',
    'openidsettings' => 'openid',
    'dbschema_node' => 'pdo',
    'dbschema_node__access' => 'pdo',
    'dbschema_node__fallback' => 'pdo',
    'dbschema_node__log' => 'pdo',
    'dbschema_node__poll' => 'pdo',
    'dbschema_node__rel' => 'pdo',
    'dbschema_node__rev' => 'pdo',
    'dbschema_node__seq' => 'pdo',
    'dbschema_node__session' => 'pdo',
    'mcms_mysql_driver' => 'pdo',
    'mcms_sqlite_driver' => 'pdo',
    'pdo_singleton' => 'pdo',
    'tableinfo' => 'pdo',
    'tablemanager' => 'pdo',
    'mcmspdoexception' => 'pdo',
    'readonlydatabaseexception' => 'pdo',
    'tablenotfoundexception' => 'pdo',
    'tinymcemodule' => 'tinymce',
    'updatemenu' => 'update',
    'updater' => 'update',
    'updatescheduler' => 'update',
  ),
);