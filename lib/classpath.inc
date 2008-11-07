<?php return array (
  'modules' => 
  array (
    'accesslog' => 
    array (
      'classes' => 
      array (
        0 => 'AccessLogListHandler',
        1 => 'AccessLogModule',
        2 => 'AccessLogRPC',
        3 => 'DBSchema_node__astat',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminMenu',
        1 => 'iModuleConfig',
        2 => 'iRequestHook',
        3 => 'iAdminUI',
        4 => 'iWidget',
        5 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iAdminMenu' => 
        array (
          0 => 'AccessLogModule',
        ),
        'iModuleConfig' => 
        array (
          0 => 'AccessLogModule',
        ),
        'iRequestHook' => 
        array (
          0 => 'AccessLogModule',
        ),
        'iAdminUI' => 
        array (
          0 => 'AccessLogModule',
        ),
        'iWidget' => 
        array (
          0 => 'AccessLogModule',
        ),
        'iRemoteCall' => 
        array (
          0 => 'AccessLogRPC',
        ),
      ),
      'enabled' => true,
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
        0 => 'AdminExtReqHook',
        1 => 'AdminExtRPC',
        2 => 'AdminExtSettings',
      ),
      'interfaces' => 
      array (
        0 => 'iRequestHook',
        1 => 'iRemoteCall',
        2 => 'iModuleConfig',
      ),
      'implementors' => 
      array (
        'iRequestHook' => 
        array (
          0 => 'AdminExtReqHook',
        ),
        'iRemoteCall' => 
        array (
          0 => 'AdminExtRPC',
        ),
        'iModuleConfig' => 
        array (
          0 => 'AdminExtSettings',
        ),
      ),
      'enabled' => true,
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
        0 => 'AutocompleteModule',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'AutocompleteModule',
        ),
      ),
      'enabled' => true,
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
        2 => 'Calendar',
        3 => 'Config',
        4 => 'Context',
        5 => 'Control',
        6 => 'Form',
        7 => 'Installer',
        8 => 'mcms',
        9 => 'Node',
        10 => 'NodeBase',
        11 => 'NodeMover',
        12 => 'NodeQueryBuilder',
        13 => 'RequestController',
        14 => 'Schema',
        15 => 'Session',
        16 => 'url',
        17 => 'Widget',
        18 => 'AccessControl',
        19 => 'ActionsControl',
        20 => 'BoolControl',
        21 => 'DateControl',
        22 => 'DateTimeControl',
        23 => 'EmailControl',
        24 => 'EnumControl',
        25 => 'EnumRadioControl',
        26 => 'FieldControl',
        27 => 'FieldSetControl',
        28 => 'FloatControl',
        29 => 'HiddenControl',
        30 => 'HistoryControl',
        31 => 'InfoControl',
        32 => 'NodeLinkControl',
        33 => 'NumberControl',
        34 => 'PagerControl',
        35 => 'PasswordControl',
        36 => 'ResetControl',
        37 => 'SectionControl',
        38 => 'SectionsControl',
        39 => 'SetControl',
        40 => 'SubmitControl',
        41 => 'TableControl',
        42 => 'TextAreaControl',
        43 => 'TextHTMLControl',
        44 => 'TextLineControl',
        45 => 'URLControl',
        46 => 'BadRequestException',
        47 => 'DuplicateException',
        48 => 'ForbiddenException',
        49 => 'NoIndexException',
        50 => 'NotInstalledException',
        51 => 'ObjectNotFoundException',
        52 => 'PageNotFoundException',
        53 => 'UnauthorizedException',
        54 => 'UserErrorException',
        55 => 'ValidationException',
        56 => 'WidgetHaltedException',
        57 => 'DomainNode',
        58 => 'FileNode',
        59 => 'GroupNode',
        60 => 'TagNode',
        61 => 'TypeNode',
        62 => 'UserNode',
        63 => 'WidgetNode',
        64 => 'ArchiveWidget',
        65 => 'DocWidget',
        66 => 'FormWidget',
        67 => 'LinkedNodesWidget',
        68 => 'ListWidget',
        69 => 'MenuWidget',
        70 => 'TagCloudWidget',
        71 => 'TagsWidget',
        72 => 'TitleWidget',
        73 => 'UserWidget',
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
          3 => 'ActionsControl',
          4 => 'BoolControl',
          5 => 'DateControl',
          6 => 'DateTimeControl',
          7 => 'EmailControl',
          8 => 'EnumControl',
          9 => 'EnumRadioControl',
          10 => 'FieldControl',
          11 => 'FieldSetControl',
          12 => 'FloatControl',
          13 => 'HiddenControl',
          14 => 'HistoryControl',
          15 => 'InfoControl',
          16 => 'NodeLinkControl',
          17 => 'NumberControl',
          18 => 'PagerControl',
          19 => 'PasswordControl',
          20 => 'ResetControl',
          21 => 'SectionControl',
          22 => 'SectionsControl',
          23 => 'SetControl',
          24 => 'SubmitControl',
          25 => 'TableControl',
          26 => 'TextAreaControl',
          27 => 'TextHTMLControl',
          28 => 'TextLineControl',
          29 => 'URLControl',
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
        0 => 'BlogWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iWidget' => 
        array (
          0 => 'BlogWidget',
        ),
      ),
      'enabled' => true,
      'group' => 'Service',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'User Blogs',
        'ru' => 'Блоги пользователей',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_blog',
    ),
    'cache' => 
    array (
      'classes' => 
      array (
        0 => 'APC_Cache',
        1 => 'BebopCache',
        2 => 'DBCache',
        3 => 'Local_Cache',
        4 => 'MemCacheD_Cache',
      ),
      'interfaces' => 
      array (
        0 => 'iBebopCacheEngine',
      ),
      'implementors' => 
      array (
        'iBebopCacheEngine' => 
        array (
          0 => 'APC_Cache',
          1 => 'DBCache',
          2 => 'Local_Cache',
          3 => 'MemCacheD_Cache',
        ),
      ),
      'enabled' => true,
      'group' => 'Core',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Cache Engine',
        'ru' => 'Кэширование данных',
      ),
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/mod_cache',
    ),
    'captcha' => 
    array (
      'classes' => 
      array (
        0 => 'CaptchaModule',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'CaptchaModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'CartRPC',
        1 => 'CartSettings',
        2 => 'CartControl',
        3 => 'OrderDetailsControl',
        4 => 'OrderNode',
        5 => 'CartWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iModuleConfig',
        2 => 'iFormControl',
        3 => 'iContentType',
        4 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'CartRPC',
        ),
        'iModuleConfig' => 
        array (
          0 => 'CartSettings',
        ),
        'iFormControl' => 
        array (
          0 => 'CartControl',
          1 => 'OrderDetailsControl',
        ),
        'iContentType' => 
        array (
          0 => 'OrderNode',
          1 => 'OrderNode',
        ),
        'iWidget' => 
        array (
          0 => 'CartWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'CommentListHandler',
        1 => 'CommentNodeHook',
        2 => 'CommentRPC',
        3 => 'CommentNode',
        4 => 'CommentWidget',
        5 => 'CommentFormWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminUI',
        1 => 'iAdminMenu',
        2 => 'iNodeHook',
        3 => 'iRemoteCall',
        4 => 'iContentType',
        5 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iAdminUI' => 
        array (
          0 => 'CommentListHandler',
        ),
        'iAdminMenu' => 
        array (
          0 => 'CommentListHandler',
        ),
        'iNodeHook' => 
        array (
          0 => 'CommentNodeHook',
        ),
        'iRemoteCall' => 
        array (
          0 => 'CommentRPC',
        ),
        'iContentType' => 
        array (
          0 => 'CommentNode',
        ),
        'iWidget' => 
        array (
          0 => 'CommentWidget',
          1 => 'CommentFormWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'DrawTextModule',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iRemoteCall',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'DrawTextModule',
        ),
        'iRemoteCall' => 
        array (
          0 => 'DrawTextModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'ID3Tools',
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => true,
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
        0 => 'GoogleAnalyticsModule',
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
          0 => 'GoogleAnalyticsModule',
        ),
        'iPageHook' => 
        array (
          0 => 'GoogleAnalyticsModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'MaintenanceModule',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iRequestHook',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'MaintenanceModule',
        ),
        'iRequestHook' => 
        array (
          0 => 'MaintenanceModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'ModeratorModule',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iNodeHook',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'ModeratorModule',
        ),
        'iNodeHook' => 
        array (
          0 => 'ModeratorModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'MsgModule',
        1 => 'MessageNode',
        2 => 'MessageListWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iContentType',
        2 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'MsgModule',
        ),
        'iContentType' => 
        array (
          0 => 'MessageNode',
        ),
        'iWidget' => 
        array (
          0 => 'MessageListWidget',
        ),
      ),
      'enabled' => true,
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
        2 => 'DBSchema_node__cache',
        3 => 'DBSchema_node__fallback',
        4 => 'DBSchema_node__log',
        5 => 'DBSchema_node__poll',
        6 => 'DBSchema_node__rel',
        7 => 'DBSchema_node__rev',
        8 => 'DBSchema_node__seq',
        9 => 'DBSchema_node__session',
        10 => 'mcms_mysql_driver',
        11 => 'mcms_sqlite_driver',
        12 => 'PDO_Singleton',
        13 => 'TableInfo',
        14 => 'TableManager',
        15 => 'McmsPDOException',
        16 => 'ReadOnlyDatabaseException',
        17 => 'TableNotFoundException',
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
        0 => 'XspfModule',
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
          0 => 'XspfModule',
        ),
        'iModuleConfig' => 
        array (
          0 => 'XspfModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'PollRPC',
        1 => 'PollNode',
        2 => 'PollWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iContentType',
        2 => 'iModuleConfig',
        3 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'PollRPC',
        ),
        'iContentType' => 
        array (
          0 => 'PollNode',
          1 => 'PollNode',
        ),
        'iModuleConfig' => 
        array (
          0 => 'PollWidget',
        ),
        'iWidget' => 
        array (
          0 => 'PollWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'RatingWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iNodeHook',
        1 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iNodeHook' => 
        array (
          0 => 'RatingWidget',
        ),
        'iWidget' => 
        array (
          0 => 'RatingWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'RobotsModule',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iRequestHook',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'RobotsModule',
        ),
        'iRequestHook' => 
        array (
          0 => 'RobotsModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'RSSListHandler',
        1 => 'RSSModule',
        2 => 'RssfeedNode',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iAdminMenu',
        2 => 'iAdminUI',
        3 => 'iContentType',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'RSSModule',
        ),
        'iAdminMenu' => 
        array (
          0 => 'RSSModule',
        ),
        'iAdminUI' => 
        array (
          0 => 'RSSModule',
        ),
        'iContentType' => 
        array (
          0 => 'RssfeedNode',
        ),
      ),
      'enabled' => true,
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
        0 => 'DBSchema_node__searchindex',
        1 => 'SearchConfig',
        2 => 'SearchIndexer',
        3 => 'SearchScheduler',
        4 => 'SearchWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iNodeHook',
        2 => 'iScheduler',
        3 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'SearchConfig',
        ),
        'iNodeHook' => 
        array (
          0 => 'SearchIndexer',
        ),
        'iScheduler' => 
        array (
          0 => 'SearchScheduler',
        ),
        'iWidget' => 
        array (
          0 => 'SearchWidget',
          1 => 'SearchWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'Sitemap',
      ),
      'interfaces' => 
      array (
        0 => 'iModuleConfig',
        1 => 'iRemoteCall',
        2 => 'iNodeHook',
      ),
      'implementors' => 
      array (
        'iModuleConfig' => 
        array (
          0 => 'Sitemap',
        ),
        'iRemoteCall' => 
        array (
          0 => 'Sitemap',
        ),
        'iNodeHook' => 
        array (
          0 => 'Sitemap',
        ),
      ),
      'enabled' => true,
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
        0 => 'BebopSmarty',
        1 => 'SmartyException',
      ),
      'interfaces' => 
      array (
      ),
      'implementors' => 
      array (
      ),
      'enabled' => true,
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
        0 => 'SubscriptionAdmin',
        1 => 'SubscriptionAdminList',
        2 => 'SubscriptionConfig',
        3 => 'SubscriptionRPC',
        4 => 'SubscriptionScheduler',
        5 => 'SubscriptionWidget',
        6 => 'SubscriptionAdminWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminMenu',
        1 => 'iAdminUI',
        2 => 'iModuleConfig',
        3 => 'iRemoteCall',
        4 => 'iScheduler',
        5 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iAdminMenu' => 
        array (
          0 => 'SubscriptionAdmin',
        ),
        'iAdminUI' => 
        array (
          0 => 'SubscriptionAdminList',
        ),
        'iModuleConfig' => 
        array (
          0 => 'SubscriptionConfig',
        ),
        'iRemoteCall' => 
        array (
          0 => 'SubscriptionRPC',
        ),
        'iScheduler' => 
        array (
          0 => 'SubscriptionScheduler',
        ),
        'iWidget' => 
        array (
          0 => 'SubscriptionWidget',
          1 => 'SubscriptionAdminWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'SyslogListHandler',
        1 => 'SysLogModule',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminUI',
        1 => 'iAdminMenu',
      ),
      'implementors' => 
      array (
        'iAdminUI' => 
        array (
          0 => 'SysLogModule',
        ),
        'iAdminMenu' => 
        array (
          0 => 'SysLogModule',
        ),
      ),
      'enabled' => true,
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
        0 => 'TodoRPC',
        1 => 'TodoNode',
        2 => 'TodoListWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iRemoteCall',
        1 => 'iContentType',
        2 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iRemoteCall' => 
        array (
          0 => 'TodoRPC',
        ),
        'iContentType' => 
        array (
          0 => 'TodoNode',
        ),
        'iWidget' => 
        array (
          0 => 'TodoListWidget',
        ),
      ),
      'enabled' => true,
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
        0 => 'XmlImporter',
      ),
      'interfaces' => 
      array (
        0 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iWidget' => 
        array (
          0 => 'XmlImporter',
        ),
      ),
      'enabled' => true,
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
    'accessloglisthandler' => 'lib/modules/accesslog/class.accessloglisthandler.php',
    'accesslogmodule' => 'lib/modules/accesslog/class.accesslogmodule.php',
    'accesslogrpc' => 'lib/modules/accesslog/class.accesslogrpc.php',
    'actionscontrol' => 'lib/modules/base/control.actions.php',
    'adminextreqhook' => 'lib/modules/adminext/class.adminextreqhook.php',
    'adminextrpc' => 'lib/modules/adminext/class.adminextrpc.php',
    'adminextsettings' => 'lib/modules/adminext/class.adminextsettings.php',
    'adminlisthandler' => 'lib/modules/admin/class.adminlisthandler.php',
    'adminmenu' => 'lib/modules/admin/class.adminmenu.php',
    'adminrpc' => 'lib/modules/admin/class.adminrpc.php',
    'adminstatus' => 'lib/modules/admin/class.adminstatus.php',
    'admintreehandler' => 'lib/modules/admin/class.admintreehandler.php',
    'adminuilistcontrol' => 'lib/modules/admin/control.adminuilist.php',
    'adminuinodeactionscontrol' => 'lib/modules/admin/control.adminuinodeactions.php',
    'adminuisearchcontrol' => 'lib/modules/admin/control.adminuisearch.php',
    'adminuitreecontrol' => 'lib/modules/admin/control.adminuitree.php',
    'apc_cache' => 'lib/modules/cache/class.apc_cache.php',
    'archivewidget' => 'lib/modules/base/widget.archive.php',
    'attachment' => 'lib/modules/attachment/class.attachment.php',
    'attachmentcontrol' => 'lib/modules/attachment/control.attachment.php',
    'attachmentmodule' => 'lib/modules/attachment/class.attachmentmodule.php',
    'autocompletemodule' => 'lib/modules/autocomplete/class.autocompletemodule.php',
    'badrequestexception' => 'lib/modules/base/exception.badrequest.php',
    'basecron' => 'lib/modules/base/class.basecron.php',
    'basemodule' => 'lib/modules/base/class.basemodule.php',
    'bebopcache' => 'lib/modules/cache/class.bebopcache.php',
    'bebopmimemail' => 'lib/modules/mimemail/class.bebopmimemail.php',
    'bebopsmarty' => 'lib/modules/smarty/class.bebopsmarty.php',
    'blogwidget' => 'lib/modules/blog/widget.blog.php',
    'boolcontrol' => 'lib/modules/base/control.bool.php',
    'calendar' => 'lib/modules/base/class.calendar.php',
    'captchamodule' => 'lib/modules/captcha/class.captchamodule.php',
    'cartcontrol' => 'lib/modules/cart/control.cart.php',
    'cartrpc' => 'lib/modules/cart/class.cartrpc.php',
    'cartsettings' => 'lib/modules/cart/class.cartsettings.php',
    'cartwidget' => 'lib/modules/cart/widget.cart.php',
    'commentformwidget' => 'lib/modules/comment/widget.commentform.php',
    'commentlisthandler' => 'lib/modules/comment/class.commentlisthandler.php',
    'commentnode' => 'lib/modules/comment/node.comment.php',
    'commentnodehook' => 'lib/modules/comment/class.commentnodehook.php',
    'commentrpc' => 'lib/modules/comment/class.commentrpc.php',
    'commentwidget' => 'lib/modules/comment/widget.comment.php',
    'compressormodule' => 'lib/modules/compressor/class.compressormodule.php',
    'compressorsettings' => 'lib/modules/compressor/class.compressorsettings.php',
    'config' => 'lib/modules/base/class.config.php',
    'context' => 'lib/modules/base/class.context.php',
    'control' => 'lib/modules/base/class.control.php',
    'cronmenu' => 'lib/modules/cron/class.cronmenu.php',
    'cronmodule' => 'lib/modules/cron/class.cronmodule.php',
    'datecontrol' => 'lib/modules/base/control.date.php',
    'datetimecontrol' => 'lib/modules/base/control.datetime.php',
    'dbcache' => 'lib/modules/cache/class.dbcache.php',
    'dbschema_node' => 'lib/modules/pdo/class.dbschema_node.php',
    'dbschema_node__access' => 'lib/modules/pdo/class.dbschema_node__access.php',
    'dbschema_node__astat' => 'lib/modules/accesslog/class.dbschema_node__astat.php',
    'dbschema_node__cache' => 'lib/modules/pdo/class.dbschema_node__cache.php',
    'dbschema_node__fallback' => 'lib/modules/pdo/class.dbschema_node__fallback.php',
    'dbschema_node__log' => 'lib/modules/pdo/class.dbschema_node__log.php',
    'dbschema_node__poll' => 'lib/modules/pdo/class.dbschema_node__poll.php',
    'dbschema_node__rel' => 'lib/modules/pdo/class.dbschema_node__rel.php',
    'dbschema_node__rev' => 'lib/modules/pdo/class.dbschema_node__rev.php',
    'dbschema_node__searchindex' => 'lib/modules/search/class.dbschema_node__searchindex.php',
    'dbschema_node__seq' => 'lib/modules/pdo/class.dbschema_node__seq.php',
    'dbschema_node__session' => 'lib/modules/pdo/class.dbschema_node__session.php',
    'docwidget' => 'lib/modules/base/widget.doc.php',
    'domainnode' => 'lib/modules/base/node.domain.php',
    'drawtextmodule' => 'lib/modules/drawtext/class.drawtextmodule.php',
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
    'googleanalyticsmodule' => 'lib/modules/googleanalytics/class.googleanalyticsmodule.php',
    'groupnode' => 'lib/modules/base/node.group.php',
    'hiddencontrol' => 'lib/modules/base/control.hidden.php',
    'historycontrol' => 'lib/modules/base/control.history.php',
    'htmlmimemail' => 'lib/modules/mimemail/class.htmlmimemail.php',
    'iadminmenu' => 'lib/modules/base/interface.adminmenu.php',
    'iadminui' => 'lib/modules/admin/interface.adminui.php',
    'ibebopcacheengine' => 'lib/modules/cache/interface.bebopcacheengine.php',
    'icontenttype' => 'lib/modules/base/interface.contenttype.php',
    'id3tools' => 'lib/modules/getid3/class.id3tools.php',
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
    'listwidget' => 'lib/modules/base/widget.list.php',
    'local_cache' => 'lib/modules/cache/class.local_cache.php',
    'mail_mimepart' => 'lib/modules/mimemail/class.mail_mimepart.php',
    'mail_rfc822' => 'lib/modules/mimemail/class.mail_rfc822.php',
    'maintenancemodule' => 'lib/modules/maintenance/class.maintenancemodule.php',
    'mcms' => 'lib/modules/base/class.mcms.php',
    'mcms_mysql_driver' => 'lib/modules/pdo/class.mcms_mysql_driver.php',
    'mcms_sqlite_driver' => 'lib/modules/pdo/class.mcms_sqlite_driver.php',
    'mcmspdoexception' => 'lib/modules/pdo/exception.mcmspdo.php',
    'memcached_cache' => 'lib/modules/cache/class.memcached_cache.php',
    'menuwidget' => 'lib/modules/base/widget.menu.php',
    'messagelistwidget' => 'lib/modules/msg/widget.messagelist.php',
    'messagenode' => 'lib/modules/msg/node.message.php',
    'moderatormodule' => 'lib/modules/moderator/class.moderatormodule.php',
    'moduleadminui' => 'lib/modules/admin/class.moduleadminui.php',
    'msgmodule' => 'lib/modules/msg/class.msgmodule.php',
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
    'orderdetailscontrol' => 'lib/modules/cart/control.orderdetails.php',
    'ordernode' => 'lib/modules/cart/node.order.php',
    'pagenotfoundexception' => 'lib/modules/base/exception.pagenotfound.php',
    'pagercontrol' => 'lib/modules/base/control.pager.php',
    'passwordcontrol' => 'lib/modules/base/control.password.php',
    'pdo_singleton' => 'lib/modules/pdo/class.pdo_singleton.php',
    'pollnode' => 'lib/modules/poll/node.poll.php',
    'pollrpc' => 'lib/modules/poll/class.pollrpc.php',
    'pollwidget' => 'lib/modules/poll/widget.poll.php',
    'ratingwidget' => 'lib/modules/rating/widget.rating.php',
    'readonlydatabaseexception' => 'lib/modules/pdo/exception.readonlydatabase.php',
    'requestcontroller' => 'lib/modules/base/class.requestcontroller.php',
    'resetcontrol' => 'lib/modules/base/control.reset.php',
    'robotsmodule' => 'lib/modules/robots/class.robotsmodule.php',
    'rssfeednode' => 'lib/modules/rss/node.rssfeed.php',
    'rsslisthandler' => 'lib/modules/rss/class.rsslisthandler.php',
    'rssmodule' => 'lib/modules/rss/class.rssmodule.php',
    'schema' => 'lib/modules/base/class.schema.php',
    'searchconfig' => 'lib/modules/search/class.searchconfig.php',
    'searchindexer' => 'lib/modules/search/class.searchindexer.php',
    'searchscheduler' => 'lib/modules/search/class.searchscheduler.php',
    'searchwidget' => 'lib/modules/search/widget.search.php',
    'sectioncontrol' => 'lib/modules/base/control.section.php',
    'sectionscontrol' => 'lib/modules/base/control.sections.php',
    'session' => 'lib/modules/base/class.session.php',
    'setcontrol' => 'lib/modules/base/control.set.php',
    'sitemap' => 'lib/modules/sitemap/class.sitemap.php',
    'smartyexception' => 'lib/modules/smarty/exception.smarty.php',
    'smtp' => 'lib/modules/mimemail/class.smtp.php',
    'submitcontrol' => 'lib/modules/base/control.submit.php',
    'subscriptionadmin' => 'lib/modules/subscription/class.subscriptionadmin.php',
    'subscriptionadminlist' => 'lib/modules/subscription/class.subscriptionadminlist.php',
    'subscriptionadminwidget' => 'lib/modules/subscription/widget.subscriptionadmin.php',
    'subscriptionconfig' => 'lib/modules/subscription/class.subscriptionconfig.php',
    'subscriptionrpc' => 'lib/modules/subscription/class.subscriptionrpc.php',
    'subscriptionscheduler' => 'lib/modules/subscription/class.subscriptionscheduler.php',
    'subscriptionwidget' => 'lib/modules/subscription/widget.subscription.php',
    'sysloglisthandler' => 'lib/modules/syslog/class.sysloglisthandler.php',
    'syslogmodule' => 'lib/modules/syslog/class.syslogmodule.php',
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
    'todolistwidget' => 'lib/modules/todo/widget.todolist.php',
    'todonode' => 'lib/modules/todo/node.todo.php',
    'todorpc' => 'lib/modules/todo/class.todorpc.php',
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
    'xmlimporter' => 'lib/modules/xmlimport/class.xmlimporter.php',
    'xspfmodule' => 'lib/modules/playlist/class.xspfmodule.php',
  ),
  'interfaces' => 
  array (
    'iadminmenu' => 
    array (
      0 => 'accesslogmodule',
      1 => 'adminmenu',
      2 => 'adminstatus',
      3 => 'commentlisthandler',
      4 => 'cronmenu',
      5 => 'exchangemodule',
      6 => 'rssmodule',
      7 => 'subscriptionadmin',
      8 => 'syslogmodule',
      9 => 'updatemenu',
    ),
    'iadminui' => 
    array (
      0 => 'accesslogmodule',
      1 => 'commentlisthandler',
      2 => 'exchangemodule',
      3 => 'rssmodule',
      4 => 'subscriptionadminlist',
      5 => 'syslogmodule',
      6 => 'updater',
    ),
    'ibebopcacheengine' => 
    array (
      0 => 'apc_cache',
      1 => 'dbcache',
      2 => 'local_cache',
      3 => 'memcached_cache',
    ),
    'icontenttype' => 
    array (
      0 => 'commentnode',
      1 => 'domainnode',
      2 => 'filenode',
      3 => 'groupnode',
      4 => 'messagenode',
      5 => 'node',
      6 => 'ordernode',
      7 => 'pollnode',
      8 => 'rssfeednode',
      9 => 'tagnode',
      10 => 'todonode',
      11 => 'typenode',
      12 => 'usernode',
      13 => 'widgetnode',
    ),
    'iformcontrol' => 
    array (
      0 => 'accesscontrol',
      1 => 'actionscontrol',
      2 => 'adminuilistcontrol',
      3 => 'adminuinodeactionscontrol',
      4 => 'adminuisearchcontrol',
      5 => 'adminuitreecontrol',
      6 => 'attachmentcontrol',
      7 => 'boolcontrol',
      8 => 'cartcontrol',
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
      22 => 'nodelinkcontrol',
      23 => 'numbercontrol',
      24 => 'orderdetailscontrol',
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
      0 => 'accesslogmodule',
      1 => 'adminextsettings',
      2 => 'basemodule',
      3 => 'bebopmimemail',
      4 => 'cartsettings',
      5 => 'compressorsettings',
      6 => 'cronmodule',
      7 => 'drawtextmodule',
      8 => 'googleanalyticsmodule',
      9 => 'maintenancemodule',
      10 => 'moderatormodule',
      11 => 'openidsettings',
      12 => 'pollwidget',
      13 => 'robotsmodule',
      14 => 'searchconfig',
      15 => 'sitemap',
      16 => 'subscriptionconfig',
      17 => 'tinymcemodule',
      18 => 'typenode',
      19 => 'xspfmodule',
    ),
    'inodehook' => 
    array (
      0 => 'basemodule',
      1 => 'commentnodehook',
      2 => 'moderatormodule',
      3 => 'ratingwidget',
      4 => 'searchindexer',
      5 => 'sitemap',
    ),
    'ipagehook' => 
    array (
      0 => 'googleanalyticsmodule',
      1 => 'tinymcemodule',
    ),
    'iremotecall' => 
    array (
      0 => 'accesslogrpc',
      1 => 'adminextrpc',
      2 => 'adminrpc',
      3 => 'attachmentmodule',
      4 => 'autocompletemodule',
      5 => 'basemodule',
      6 => 'captchamodule',
      7 => 'cartrpc',
      8 => 'commentrpc',
      9 => 'compressormodule',
      10 => 'cronmodule',
      11 => 'drawtextmodule',
      12 => 'exchangemodule',
      13 => 'imageedit',
      14 => 'installmodule',
      15 => 'msgmodule',
      16 => 'nodeapimodule',
      17 => 'openidmodule',
      18 => 'pollrpc',
      19 => 'rssmodule',
      20 => 'sitemap',
      21 => 'subscriptionrpc',
      22 => 'todorpc',
      23 => 'updater',
      24 => 'xspfmodule',
    ),
    'irequesthook' => 
    array (
      0 => 'accesslogmodule',
      1 => 'adminextreqhook',
      2 => 'maintenancemodule',
      3 => 'robotsmodule',
    ),
    'ischeduler' => 
    array (
      0 => 'basecron',
      1 => 'searchscheduler',
      2 => 'subscriptionscheduler',
      3 => 'typenode',
      4 => 'updatescheduler',
    ),
    'iwidget' => 
    array (
      0 => 'accesslogmodule',
      1 => 'archivewidget',
      2 => 'blogwidget',
      3 => 'cartwidget',
      4 => 'commentformwidget',
      5 => 'commentwidget',
      6 => 'docwidget',
      7 => 'formwidget',
      8 => 'linkednodeswidget',
      9 => 'listwidget',
      10 => 'menuwidget',
      11 => 'messagelistwidget',
      12 => 'pollwidget',
      13 => 'ratingwidget',
      14 => 'searchwidget',
      15 => 'subscriptionadminwidget',
      16 => 'subscriptionwidget',
      17 => 'tagcloudwidget',
      18 => 'tagswidget',
      19 => 'titlewidget',
      20 => 'todolistwidget',
      21 => 'userwidget',
      22 => 'widget',
      23 => 'xmlimporter',
    ),
  ),
  'rclasses' => 
  array (
    'accessloglisthandler' => 'accesslog',
    'accesslogmodule' => 'accesslog',
    'accesslogrpc' => 'accesslog',
    'dbschema_node__astat' => 'accesslog',
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
    'adminextreqhook' => 'adminext',
    'adminextrpc' => 'adminext',
    'adminextsettings' => 'adminext',
    'attachment' => 'attachment',
    'attachmentmodule' => 'attachment',
    'attachmentcontrol' => 'attachment',
    'user' => 'auth',
    'autocompletemodule' => 'autocomplete',
    'basecron' => 'base',
    'basemodule' => 'base',
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
    'requestcontroller' => 'base',
    'schema' => 'base',
    'session' => 'base',
    'url' => 'base',
    'widget' => 'base',
    'accesscontrol' => 'base',
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
    'blogwidget' => 'blog',
    'apc_cache' => 'cache',
    'bebopcache' => 'cache',
    'dbcache' => 'cache',
    'local_cache' => 'cache',
    'memcached_cache' => 'cache',
    'ibebopcacheengine' => 'cache',
    'captchamodule' => 'captcha',
    'cartrpc' => 'cart',
    'cartsettings' => 'cart',
    'cartcontrol' => 'cart',
    'orderdetailscontrol' => 'cart',
    'ordernode' => 'cart',
    'cartwidget' => 'cart',
    'commentlisthandler' => 'comment',
    'commentnodehook' => 'comment',
    'commentrpc' => 'comment',
    'commentnode' => 'comment',
    'commentwidget' => 'comment',
    'commentformwidget' => 'comment',
    'compressormodule' => 'compressor',
    'compressorsettings' => 'compressor',
    'cronmenu' => 'cron',
    'cronmodule' => 'cron',
    'drawtextmodule' => 'drawtext',
    'exchangemodule' => 'exchange',
    'id3tools' => 'getid3',
    'googleanalyticsmodule' => 'googleanalytics',
    'imageedit' => 'imgtoolkit',
    'imagemagick' => 'imgtoolkit',
    'imagemagickgd' => 'imgtoolkit',
    'installmodule' => 'install',
    'maintenancemodule' => 'maintenance',
    'bebopmimemail' => 'mimemail',
    'htmlmimemail' => 'mimemail',
    'mail_mimepart' => 'mimemail',
    'mail_rfc822' => 'mimemail',
    'smtp' => 'mimemail',
    'moderatormodule' => 'moderator',
    'msgmodule' => 'msg',
    'messagenode' => 'msg',
    'messagelistwidget' => 'msg',
    'nodeapimodule' => 'nodeapi',
    'openidmodule' => 'openid',
    'openidsettings' => 'openid',
    'dbschema_node' => 'pdo',
    'dbschema_node__access' => 'pdo',
    'dbschema_node__cache' => 'pdo',
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
    'xspfmodule' => 'playlist',
    'pollrpc' => 'poll',
    'pollnode' => 'poll',
    'pollwidget' => 'poll',
    'ratingwidget' => 'rating',
    'robotsmodule' => 'robots',
    'rsslisthandler' => 'rss',
    'rssmodule' => 'rss',
    'rssfeednode' => 'rss',
    'dbschema_node__searchindex' => 'search',
    'searchconfig' => 'search',
    'searchindexer' => 'search',
    'searchscheduler' => 'search',
    'searchwidget' => 'search',
    'sitemap' => 'sitemap',
    'bebopsmarty' => 'smarty',
    'smartyexception' => 'smarty',
    'subscriptionadmin' => 'subscription',
    'subscriptionadminlist' => 'subscription',
    'subscriptionconfig' => 'subscription',
    'subscriptionrpc' => 'subscription',
    'subscriptionscheduler' => 'subscription',
    'subscriptionwidget' => 'subscription',
    'subscriptionadminwidget' => 'subscription',
    'sysloglisthandler' => 'syslog',
    'syslogmodule' => 'syslog',
    'tinymcemodule' => 'tinymce',
    'todorpc' => 'todo',
    'todonode' => 'todo',
    'todolistwidget' => 'todo',
    'updatemenu' => 'update',
    'updater' => 'update',
    'updatescheduler' => 'update',
    'xmlimporter' => 'xmlimport',
  ),
);