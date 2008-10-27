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
        2 => 'AdminStatus',
        3 => 'AdminTreeHandler',
        4 => 'AdminUIModule',
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
        1 => 'iAdminUI',
        2 => 'iRemoteCall',
        3 => 'iFormControl',
      ),
      'implementors' => 
      array (
        'iAdminMenu' => 
        array (
          0 => 'AdminMenu',
          1 => 'AdminStatus',
        ),
        'iAdminUI' => 
        array (
          0 => 'AdminUIModule',
        ),
        'iRemoteCall' => 
        array (
          0 => 'AdminUIModule',
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
        14 => 'Session',
        15 => 'url',
        16 => 'Widget',
        17 => 'AccessControl',
        18 => 'ActionsControl',
        19 => 'BoolControl',
        20 => 'DateControl',
        21 => 'DateTimeControl',
        22 => 'EmailControl',
        23 => 'EnumControl',
        24 => 'EnumRadioControl',
        25 => 'FieldControl',
        26 => 'FieldSetControl',
        27 => 'FloatControl',
        28 => 'HiddenControl',
        29 => 'HistoryControl',
        30 => 'InfoControl',
        31 => 'NodeLinkControl',
        32 => 'NumberControl',
        33 => 'PagerControl',
        34 => 'PasswordControl',
        35 => 'ResetControl',
        36 => 'SetControl',
        37 => 'SubmitControl',
        38 => 'TableControl',
        39 => 'TextAreaControl',
        40 => 'TextHTMLControl',
        41 => 'TextLineControl',
        42 => 'URLControl',
        43 => 'BadRequestException',
        44 => 'DuplicateException',
        45 => 'ForbiddenException',
        46 => 'NoIndexException',
        47 => 'NotInstalledException',
        48 => 'ObjectNotFoundException',
        49 => 'PageNotFoundException',
        50 => 'UnauthorizedException',
        51 => 'UserErrorException',
        52 => 'ValidationException',
        53 => 'WidgetHaltedException',
        54 => 'DomainNode',
        55 => 'FileNode',
        56 => 'GroupNode',
        57 => 'TagNode',
        58 => 'TypeNode',
        59 => 'UserNode',
        60 => 'WidgetNode',
        61 => 'ArchiveWidget',
        62 => 'DocWidget',
        63 => 'FormWidget',
        64 => 'LinkedNodesWidget',
        65 => 'ListWidget',
        66 => 'MenuWidget',
        67 => 'TagCloudWidget',
        68 => 'TagsWidget',
        69 => 'TitleWidget',
        70 => 'UserWidget',
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
          21 => 'SetControl',
          22 => 'SubmitControl',
          23 => 'TableControl',
          24 => 'TextAreaControl',
          25 => 'TextHTMLControl',
          26 => 'TextLineControl',
          27 => 'URLControl',
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
        2 => 'CommentNode',
        3 => 'CommentWidget',
        4 => 'CommentFormWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iAdminUI',
        1 => 'iAdminMenu',
        2 => 'iNodeHook',
        3 => 'iContentType',
        4 => 'iWidget',
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
        'ru' => 'Доступ к тэгам файлов',
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
    'status' => 
    array (
      'classes' => 
      array (
        0 => 'StatusAdminWidget',
      ),
      'interfaces' => 
      array (
        0 => 'iWidget',
      ),
      'implementors' => 
      array (
        'iWidget' => 
        array (
          0 => 'StatusAdminWidget',
        ),
      ),
      'enabled' => true,
      'group' => 'Statistics',
      'version' => '8.02.4782',
      'name' => 
      array (
        'en' => 'Site Status monitor',
        'ru' => 'Мониторинг состояния системы',
      ),
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
    'adminstatus' => 'lib/modules/admin/class.adminstatus.php',
    'admintreehandler' => 'lib/modules/admin/class.admintreehandler.php',
    'adminuilistcontrol' => 'lib/modules/admin/control.adminuilist.php',
    'adminuimodule' => 'lib/modules/admin/class.adminuimodule.php',
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
    'searchconfig' => 'lib/modules/search/class.searchconfig.php',
    'searchindexer' => 'lib/modules/search/class.searchindexer.php',
    'searchscheduler' => 'lib/modules/search/class.searchscheduler.php',
    'searchwidget' => 'lib/modules/search/widget.search.php',
    'session' => 'lib/modules/base/class.session.php',
    'setcontrol' => 'lib/modules/base/control.set.php',
    'sitemap' => 'lib/modules/sitemap/class.sitemap.php',
    'smartyexception' => 'lib/modules/smarty/exception.smarty.php',
    'smtp' => 'lib/modules/mimemail/class.smtp.php',
    'statusadminwidget' => 'lib/modules/status/widget.statusadmin.php',
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
    'iAdminMenu' => 
    array (
      0 => 'AccessLogModule',
      1 => 'AdminMenu',
      2 => 'AdminStatus',
      3 => 'CommentListHandler',
      4 => 'CronMenu',
      5 => 'ExchangeModule',
      6 => 'RSSModule',
      7 => 'SubscriptionAdmin',
      8 => 'SysLogModule',
      9 => 'UpdateMenu',
    ),
    'iAdminUI' => 
    array (
      0 => 'AccessLogModule',
      1 => 'AdminUIModule',
      2 => 'CommentListHandler',
      3 => 'ExchangeModule',
      4 => 'RSSModule',
      5 => 'SubscriptionAdminList',
      6 => 'SysLogModule',
      7 => 'Updater',
    ),
    'iBebopCacheEngine' => 
    array (
      0 => 'APC_Cache',
      1 => 'DBCache',
      2 => 'Local_Cache',
      3 => 'MemCacheD_Cache',
    ),
    'iContentType' => 
    array (
      0 => 'CommentNode',
      1 => 'DomainNode',
      2 => 'FileNode',
      3 => 'GroupNode',
      4 => 'MessageNode',
      5 => 'Node',
      6 => 'OrderNode',
      7 => 'PollNode',
      8 => 'RssfeedNode',
      9 => 'TagNode',
      10 => 'TodoNode',
      11 => 'TypeNode',
      12 => 'UserNode',
      13 => 'WidgetNode',
    ),
    'iFormControl' => 
    array (
      0 => 'AccessControl',
      1 => 'ActionsControl',
      2 => 'AdminUIListControl',
      3 => 'AdminUINodeActionsControl',
      4 => 'AdminUISearchControl',
      5 => 'AdminUITreeControl',
      6 => 'AttachmentControl',
      7 => 'BoolControl',
      8 => 'CartControl',
      9 => 'Control',
      10 => 'DateControl',
      11 => 'DateTimeControl',
      12 => 'EmailControl',
      13 => 'EnumControl',
      14 => 'EnumRadioControl',
      15 => 'FieldControl',
      16 => 'FieldSetControl',
      17 => 'FloatControl',
      18 => 'Form',
      19 => 'HiddenControl',
      20 => 'HistoryControl',
      21 => 'InfoControl',
      22 => 'NodeLinkControl',
      23 => 'NumberControl',
      24 => 'OrderDetailsControl',
      25 => 'PagerControl',
      26 => 'PasswordControl',
      27 => 'ResetControl',
      28 => 'SetControl',
      29 => 'SubmitControl',
      30 => 'TableControl',
      31 => 'TextAreaControl',
      32 => 'TextHTMLControl',
      33 => 'TextLineControl',
      34 => 'URLControl',
    ),
    'iModuleConfig' => 
    array (
      0 => 'AccessLogModule',
      1 => 'AdminExtSettings',
      2 => 'BaseModule',
      3 => 'BebopMimeMail',
      4 => 'CartSettings',
      5 => 'CompressorSettings',
      6 => 'CronModule',
      7 => 'DrawTextModule',
      8 => 'GoogleAnalyticsModule',
      9 => 'MaintenanceModule',
      10 => 'ModeratorModule',
      11 => 'OpenIdSettings',
      12 => 'PollWidget',
      13 => 'RobotsModule',
      14 => 'SearchConfig',
      15 => 'Sitemap',
      16 => 'SubscriptionConfig',
      17 => 'TinyMceModule',
      18 => 'TypeNode',
      19 => 'XspfModule',
    ),
    'iNodeHook' => 
    array (
      0 => 'BaseModule',
      1 => 'CommentNodeHook',
      2 => 'ModeratorModule',
      3 => 'RatingWidget',
      4 => 'SearchIndexer',
      5 => 'Sitemap',
    ),
    'iPageHook' => 
    array (
      0 => 'GoogleAnalyticsModule',
      1 => 'TinyMceModule',
    ),
    'iRemoteCall' => 
    array (
      0 => 'AccessLogRPC',
      1 => 'AdminExtRPC',
      2 => 'AdminUIModule',
      3 => 'AttachmentModule',
      4 => 'AutocompleteModule',
      5 => 'BaseModule',
      6 => 'CaptchaModule',
      7 => 'CartRPC',
      8 => 'CompressorModule',
      9 => 'CronModule',
      10 => 'DrawTextModule',
      11 => 'ExchangeModule',
      12 => 'ImageEdit',
      13 => 'InstallModule',
      14 => 'MsgModule',
      15 => 'NodeApiModule',
      16 => 'OpenIdModule',
      17 => 'PollRPC',
      18 => 'RSSModule',
      19 => 'Sitemap',
      20 => 'SubscriptionRPC',
      21 => 'TodoRPC',
      22 => 'Updater',
      23 => 'XspfModule',
    ),
    'iRequestHook' => 
    array (
      0 => 'AccessLogModule',
      1 => 'AdminExtReqHook',
      2 => 'MaintenanceModule',
      3 => 'RobotsModule',
    ),
    'iScheduler' => 
    array (
      0 => 'BaseCron',
      1 => 'SearchScheduler',
      2 => 'SubscriptionScheduler',
      3 => 'TypeNode',
      4 => 'UpdateScheduler',
    ),
    'iWidget' => 
    array (
      0 => 'AccessLogModule',
      1 => 'ArchiveWidget',
      2 => 'BlogWidget',
      3 => 'CartWidget',
      4 => 'CommentFormWidget',
      5 => 'CommentWidget',
      6 => 'DocWidget',
      7 => 'FormWidget',
      8 => 'LinkedNodesWidget',
      9 => 'ListWidget',
      10 => 'MenuWidget',
      11 => 'MessageListWidget',
      12 => 'PollWidget',
      13 => 'RatingWidget',
      14 => 'SearchWidget',
      15 => 'StatusAdminWidget',
      16 => 'SubscriptionAdminWidget',
      17 => 'SubscriptionWidget',
      18 => 'TagCloudWidget',
      19 => 'TagsWidget',
      20 => 'TitleWidget',
      21 => 'TodoListWidget',
      22 => 'UserWidget',
      23 => 'Widget',
      24 => 'XmlImporter',
    ),
  ),
);