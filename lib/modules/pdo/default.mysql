SET NAMES utf8;
DROP TABLE IF EXISTS `node__idx_file`; CREATE TABLE `node__idx_file` (`id` int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT, `filename` VARCHAR(255) NOT NULL, `filetype` VARCHAR(255) NOT NULL, `filesize` DECIMAL(10,2) NOT NULL, `filepath` VARCHAR(255) NOT NULL);
DROP TABLE IF EXISTS `node__rel`; CREATE TABLE `node__rel` (`nid` int NOT NULL, `tid` int NOT NULL, `key` varchar(255) NULL, `order` int NULL);
DROP TABLE IF EXISTS `node__access`; CREATE TABLE `node__access` (`nid` int NOT NULL, `uid` int NOT NULL, `c` tinyint(1) NOT NULL DEFAULT 0, `r` tinyint(1) NOT NULL DEFAULT 0, `u` tinyint(1) NOT NULL DEFAULT 0, `d` tinyint(1) NOT NULL DEFAULT 0, `p` tinyint(1) NOT NULL DEFAULT 0);
DROP TABLE IF EXISTS `node__fallback`; CREATE TABLE `node__fallback` (`old` varchar(255) NOT NULL, `new` varchar(255) NULL, `ref` varchar(255) NULL);
DROP TABLE IF EXISTS `node`; CREATE TABLE "node" (`id` integer NOT NULL PRIMARY KEY AUTO_INCREMENT, `lang` char(4) NOT NULL, `parent_id` int NULL, `class` varchar(16) NOT NULL, `left` int NULL, `right` int NULL, `uid` int NULL, `created` datetime NULL, `updated` datetime NULL, `published` tinyint(1) NOT NULL DEFAULT 0, `deleted` tinyint(1) NOT NULL DEFAULT 0, `name` VARCHAR(255) NULL, `name_lc` VARCHAR(255) NULL, `data` MEDIUMBLOB NULL);
CREATE INDEX `IDX_node__idx_file_filename` on `node__idx_file` (`filename`);
CREATE INDEX `IDX_node__idx_file_filetype` on `node__idx_file` (`filetype`);
CREATE INDEX `IDX_node__idx_file_filesize` on `node__idx_file` (`filesize`);
CREATE INDEX `IDX_node__idx_file_filepath` on `node__idx_file` (`filepath`);
CREATE INDEX `IDX_node__rel_nid` on `node__rel` (`nid`);
CREATE INDEX `IDX_node__rel_tid` on `node__rel` (`tid`);
CREATE INDEX `IDX_node__rel_key` on `node__rel` (`key`);
CREATE INDEX `IDX_node__rel_order` on `node__rel` (`order`);
CREATE INDEX `IDX_node__access_nid` on `node__access` (`nid`);
CREATE INDEX `IDX_node__access_uid` on `node__access` (`uid`);
CREATE INDEX `IDX_node__fallback_old` on `node__fallback` (`old`);
CREATE INDEX IDX_node_lang ON node (lang);
CREATE INDEX IDX_node_parent_id ON node (parent_id);
CREATE INDEX IDX_node_class ON node (class);
CREATE INDEX IDX_node_left ON node (left);
CREATE INDEX IDX_node_right ON node (right);
CREATE INDEX IDX_node_uid ON node (uid);
CREATE INDEX IDX_node_created ON node (created);
CREATE INDEX IDX_node_updated ON node (updated);
CREATE INDEX IDX_node_published ON node (published);
CREATE INDEX IDX_node_deleted ON node (deleted);
CREATE INDEX IDX_node_name ON node (name);
CREATE INDEX IDX_node_name_lc ON node (name_lc);
CREATE UNIQUE INDEX `IDX_node__access_key` ON `node__access` (`nid`, `uid`);
INSERT INTO `node__rel` (`nid`,`tid`,`key`,`order`) VALUES
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,13,NULL,1),
 (22,18,NULL,3),
 (20,9,NULL,3),
 (22,9,NULL,4),
 (20,21,NULL,1),
 (29,9,NULL,5),
 (29,21,NULL,2),
 (33,26,NULL,1),
 (32,25,NULL,1),
 (18,'Array',NULL,NULL),
 (18,'Array',NULL,NULL),
 (18,'Array',NULL,NULL),
 (18,'Array',NULL,NULL),
 (18,10,NULL,NULL),
 (19,10,NULL,NULL);
INSERT INTO `node__access` (`nid`,`uid`,`c`,`r`,`u`,`d`,`p`) VALUES
 (6,15,0,1,0,0,0),
 (11,15,0,1,0,0,0),
 (14,15,0,1,0,0,0),
 (2,15,1,1,1,1,1),
 (16,15,0,1,0,0,0),
 (4,15,1,1,1,1,1),
 (6,13,1,1,1,1,1),
 (11,13,1,1,1,1,1),
 (14,13,1,1,1,1,1),
 (2,13,1,1,1,1,1),
 (16,13,1,1,1,1,1),
 (4,13,1,1,1,1,1),
 (5,15,1,1,1,1,1),
 (5,13,1,1,1,1,1),
 (3,15,0,1,0,0,0),
 (3,13,1,1,1,1,1),
 (10,15,1,0,0,0,0),
 (25,15,1,0,0,0,0),
 (26,15,1,0,0,0,0),
 (10,13,1,0,0,0,0),
 (25,13,1,0,0,0,0),
 (26,13,1,0,0,0,0),
 (18,15,1,1,1,1,1),
 (18,13,1,1,1,1,1);
INSERT INTO `node` (`id`,`lang`,`parent_id`,`class`,`left`,`right`,`uid`,`created`,`updated`,`published`,`deleted`,`name`,`name_lc`,`data`) VALUES
 (2,'ru',NULL,'type',NULL,NULL,NULL,'2008-04-07 12:15:40','2008-06-03 15:17:16',1,0,'type',NULL,'a:3:{s:11:\"description\";s:60:\"Скелет структуры типа документа.\";s:5:\"title\";s:25:\"Тип документа\";s:6:\"fields\";a:5:{s:4:\"name\";a:6:{s:5:\"label\";s:27:\"Внутреннее имя\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:172:\"Может содержать только буквы латинского алфавита, арабские цифры и символ подчёркивания («_»).\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:5:\"title\";a:6:{s:5:\"label\";s:25:\"Название типа\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:410:\"Короткое, максимально информативное описание название документа.&nbsp; Хорошо: &laquo;Статья&raquo;, &laquo;Баннер 85x31&raquo;, плохо: &laquo;Текстовый документ для отображения на сайте&raquo;, &laquo;Баннер справа сверху под тем другим баннером&raquo;.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:5:{s:5:\"label\";s:16:\"Описание\";s:4:\"type\";s:15:\"TextAreaControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:6:\"notags\";a:6:{s:5:\"label\";s:43:\"Не работает с разделами\";s:4:\"type\";s:11:\"BoolControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:8:\"hasfiles\";a:6:{s:5:\"label\";s:34:\"Работает с файлами\";s:4:\"type\";s:11:\"BoolControl\";s:11:\"description\";s:106:\"Показывать вкладку для прикрепления произвольных файлов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}}}'),
 (3,'ru',NULL,'type',NULL,NULL,8,'2008-04-07 12:15:41','2008-11-17 15:37:54',1,0,'widget','widget','a:4:{s:11:\"description\";s:73:\"Базовый блок для формирования страницы.\";s:5:\"title\";s:12:\"Виджет\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:3:{s:4:\"name\";a:8:{s:5:\"label\";s:27:\"Внутреннее имя\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:178:\"Используется для идентификации виджета внутри шаблонов, а также для поиска шаблонов для виджета.\";s:4:\"type\";s:15:\"textlinecontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:5:\"title\";a:8:{s:5:\"label\";s:16:\"Название\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:57:\"Человеческое название виджета.\";s:4:\"type\";s:15:\"textlinecontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:7:{s:5:\"label\";s:16:\"Описание\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:135:\"Краткое описание выполняемых виджетом функций и особенностей его работы.\";s:4:\"type\";s:15:\"textareacontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}'),
 (4,'ru',NULL,'type',NULL,NULL,NULL,'2008-04-07 12:15:41','2008-06-03 15:17:16',1,0,'file',NULL,'a:4:{s:11:\"description\";s:85:\"Используется для наполнения файлового архива.\";s:5:\"title\";s:8:\"Файл\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:7:{s:4:\"name\";a:6:{s:5:\"label\";s:27:\"Название файла\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:137:\"Человеческое название файла, например: &laquo;Финансовый отчёт за 2007-й год&raquo;\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:8:\"filename\";a:7:{s:5:\"label\";s:31:\"Оригинальное имя\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:543:\"Имя, которое было у файла, когда пользователь добавлял его на сайт.&nbsp; Под этим же именем файл будет сохранён, если пользователь попытается его сохранить.&nbsp; Рекомендуется использовать только латинский алфавит: Internet Explorer некорректно обрабатывает кириллицу в именах файлов при скачивании файлов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:8:\"filetype\";a:7:{s:5:\"label\";s:11:\"Тип MIME\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:185:\"Используется для определения способов обработки файла.&nbsp; Проставляется автоматически при закачке.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:8:\"filesize\";a:7:{s:5:\"label\";s:28:\"Размер в байтах\";s:4:\"type\";s:13:\"NumberControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:8:\"filepath\";a:7:{s:5:\"label\";s:41:\"Локальный путь к файлу\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:5:\"width\";a:5:{s:5:\"label\";s:12:\"Ширина\";s:4:\"type\";s:13:\"NumberControl\";s:11:\"description\";s:88:\"Проставляется только для картинок и SWF объектов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:6:\"height\";a:5:{s:5:\"label\";s:12:\"Высота\";s:4:\"type\";s:13:\"NumberControl\";s:11:\"description\";s:88:\"Проставляется только для картинок и SWF объектов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}'),
 (5,'ru',NULL,'type',NULL,NULL,8,'2008-04-07 12:15:41','2008-08-21 14:27:59',1,0,'tag',NULL,'a:5:{s:11:\"description\";s:76:\"Разделы определяют структуру информации.\";s:5:\"title\";s:23:\"Раздел сайта\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:2:{s:4:\"name\";a:4:{s:5:\"label\";s:21:\"Имя раздела\";s:4:\"type\";s:15:\"TextLineControl\";s:10:\"dictionary\";s:6:\"widget\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:3:{s:5:\"label\";s:16:\"Описание\";s:4:\"type\";s:15:\"TextAreaControl\";s:10:\"dictionary\";s:6:\"widget\";}}s:14:\"prev_published\";i:1;}'),
 (6,'ru',NULL,'type',NULL,NULL,NULL,'2008-04-07 12:15:41','2008-06-03 15:17:16',1,0,'group',NULL,'a:4:{s:11:\"description\";s:68:\"Используется для управления правами.\";s:5:\"title\";s:39:\"Группа пользователей\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:2:{s:4:\"name\";a:6:{s:5:\"label\";s:16:\"Название\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:5:{s:5:\"label\";s:16:\"Описание\";s:4:\"type\";s:15:\"TextAreaControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}'),
 (8,'ru',NULL,'user',NULL,NULL,NULL,'2008-04-07 12:15:43','2008-06-03 15:17:17',1,0,'cms-bugs@molinos.ru',NULL,'a:1:{s:8:\"fullname\";s:22:\"Разработчик\";}'),
 (9,'ru',NULL,'domain',1,4,NULL,'2008-04-07 12:15:46','2008-07-01 10:47:53',1,0,'localhost',NULL,'a:7:{s:5:\"title\";s:11:\"Molinos.CMS\";s:8:\"language\";s:2:\"ru\";s:12:\"content_type\";s:9:\"text/html\";s:7:\"aliases\";a:1:{i:0;s:0:\"\";}s:6:\"params\";s:3:\"sec\";s:5:\"theme\";s:7:\"example\";s:14:\"defaultsection\";s:2:\"10\";}'),
 (10,'ru',NULL,'tag',5,10,18,'2008-04-07 12:58:18','2008-11-17 16:25:02',1,0,'Molinos.CMS','molinos.cms','a:1:{s:11:\"description\";s:129:\"Это — начало структуры вашего сайта.  Все подразделы добавляются сюда.\";}'),
 (11,'ru',NULL,'type',NULL,NULL,61,'2008-04-07 13:20:16','2008-06-03 15:17:17',1,0,'moduleinfo',NULL,'a:4:{s:11:\"description\";s:105:\"Используется системой для хранения информации о модулях.\";s:5:\"title\";s:37:\"Конфигурация модуля\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:3:{s:4:\"name\";a:6:{s:5:\"label\";s:18:\"Заголовок\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:7:\"created\";a:5:{s:5:\"label\";s:25:\"Дата создания\";s:4:\"type\";s:15:\"DateTimeControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:3:\"uid\";a:5:{s:5:\"label\";s:10:\"Автор\";s:4:\"type\";s:15:\"NodeLinkControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:9:\"user.name\";}}}'),
 (12,'ru',NULL,'moduleinfo',NULL,NULL,18,'2008-04-08 10:06:52','2008-06-03 15:17:17',0,0,'exchange',NULL,NULL),
 (13,'ru',NULL,'group',NULL,NULL,43,'2008-04-10 13:03:46','2008-06-23 13:06:50',1,0,'Разработчики',NULL,'a:2:{s:11:\"description\";s:70:\"Пользователи из этой группы могут всё.\";s:5:\"login\";s:24:\"Разработчики\";}'),
 (14,'ru',NULL,'type',NULL,NULL,NULL,'2008-04-30 18:52:04','2008-06-03 15:17:17',1,0,'user',NULL,'a:4:{s:5:\"title\";s:39:\"Профиль пользователя\";s:11:\"adminmodule\";s:5:\"admin\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:4:{s:4:\"name\";a:6:{s:5:\"label\";s:19:\"Email или OpenID\";s:4:\"type\";s:12:\"EmailControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:8:\"fullname\";a:5:{s:5:\"label\";s:19:\"Полное имя\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:143:\"Используется в подписях к комментариям, при отправке почтовых сообщений и т.д.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:8:\"password\";a:6:{s:5:\"label\";s:12:\"Пароль\";s:4:\"type\";s:15:\"PasswordControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:5:\"email\";a:5:{s:5:\"label\";s:5:\"Email\";s:4:\"type\";s:12:\"EmailControl\";s:11:\"description\";s:47:\"Для доставки уведомлений.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}'),
 (15,'ru',NULL,'group',NULL,NULL,NULL,'2008-04-30 19:23:58','2008-06-23 13:06:45',1,0,'Администраторы',NULL,'a:2:{s:11:\"description\";s:50:\"Могут работать с контентом.\";s:5:\"login\";s:28:\"Администраторы\";}'),
 (16,'ru',NULL,'type',NULL,NULL,NULL,'2008-05-06 15:35:06','2008-06-03 15:17:17',1,0,'domain',NULL,'a:3:{s:5:\"title\";s:31:\"Типовая страница\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:9:{s:4:\"name\";a:3:{s:5:\"label\";s:6:\"Имя\";s:4:\"type\";s:15:\"TextLineControl\";s:8:\"required\";b:1;}s:5:\"title\";a:3:{s:5:\"label\";s:18:\"Заголовок\";s:4:\"type\";s:15:\"TextLineControl\";s:8:\"required\";b:1;}s:9:\"parent_id\";a:2:{s:5:\"label\";s:37:\"Родительский объект\";s:4:\"type\";s:11:\"EnumControl\";}s:7:\"aliases\";a:3:{s:5:\"label\";s:12:\"Алиасы\";s:4:\"type\";s:15:\"TextAreaControl\";s:11:\"description\";s:115:\"Список дополнительных адресов, по которым доступен этот домен.\";}s:8:\"language\";a:5:{s:5:\"label\";s:8:\"Язык\";s:4:\"type\";s:11:\"EnumControl\";s:11:\"description\";s:100:\"Язык для этой страницы, используется только шаблонами.\";s:7:\"options\";a:3:{s:2:\"ru\";s:14:\"русский\";s:2:\"en\";s:20:\"английский\";s:2:\"de\";s:16:\"немецкий\";}s:8:\"required\";b:1;}s:5:\"theme\";a:3:{s:5:\"label\";s:10:\"Шкура\";s:4:\"type\";s:11:\"EnumControl\";s:11:\"description\";s:73:\"Имя папки с шаблонами для этой страницы.\";}s:12:\"content_type\";a:4:{s:5:\"label\";s:23:\"Тип контента\";s:4:\"type\";s:11:\"EnumControl\";s:8:\"required\";b:1;s:7:\"options\";a:2:{s:9:\"text/html\";s:4:\"HTML\";s:8:\"text/xml\";s:3:\"XML\";}}s:6:\"params\";a:4:{s:5:\"label\";s:37:\"Разметка параметров\";s:4:\"type\";s:11:\"EnumControl\";s:8:\"required\";b:1;s:7:\"options\";a:4:{s:0:\"\";s:27:\"без параметров\";s:7:\"sec+doc\";s:31:\"/раздел/документ/\";s:3:\"sec\";s:14:\"/раздел/\";s:3:\"doc\";s:18:\"/документ/\";}}s:14:\"defaultsection\";a:2:{s:5:\"label\";s:29:\"Основной раздел\";s:4:\"type\";s:11:\"EnumControl\";}}}'),
 (17,'ru',NULL,'moduleinfo',NULL,NULL,NULL,'2008-05-06 17:06:15','2008-06-03 15:17:17',1,0,'auth',NULL,'a:1:{s:6:\"config\";a:4:{s:4:\"mode\";s:4:\"open\";s:14:\"profile_fields\";a:3:{i:0;s:5:\"email\";i:1;s:8:\"fullname\";i:2;s:8:\"language\";}s:11:\"groups_anon\";a:1:{i:0;s:1:\"6\";}s:6:\"groups\";a:1:{i:0;s:1:\"6\";}}}'),
 (18,'ru',NULL,'type',NULL,NULL,8,'2008-07-01 10:22:54','2009-02-12 11:36:48',1,0,'article','article','a:4:{s:6:\"fields\";a:3:{s:4:\"name\";a:5:{s:5:\"label\";s:18:\"Заголовок\";s:5:\"group\";s:33:\"Основные свойства\";s:4:\"type\";s:15:\"textlinecontrol\";s:10:\"dictionary\";s:6:\"widget\";s:8:\"required\";s:1:\"1\";}s:4:\"text\";a:5:{s:5:\"label\";s:10:\"Текст\";s:5:\"group\";s:33:\"Основные свойства\";s:4:\"type\";s:15:\"markdowncontrol\";s:10:\"dictionary\";s:6:\"widget\";s:8:\"required\";s:1:\"1\";}s:7:\"section\";a:4:{s:5:\"label\";s:44:\"Опубликовать в разделах\";s:5:\"group\";s:33:\"Основные свойства\";s:4:\"type\";s:14:\"sectioncontrol\";s:10:\"dictionary\";s:6:\"widget\";}}s:5:\"title\";s:12:\"Статья\";s:14:\"prev_published\";i:1;s:11:\"description\";s:161:\"Используется для формирования статичных страниц: информация о сайте, карта проезда итд.\";}'),
 (19,'ru',NULL,'article',NULL,NULL,8,'2008-06-25 12:09:57','2009-02-12 11:56:38',1,0,'Установка завершена','установка завершена','a:1:{s:4:\"text\";s:1492:\"Поздравляем, вы успешно установили Molinos CMS.\r\n\r\nЧто вам нужно знать:\r\n\r\n1. Прежде всего, ознакомьтесь с [руководством по сборке сайта](http://code.google.com/p/molinos-cms/wiki/RUUserGuide). Там вы найдёте объяснение базовых принципов работы CMS и инструкцию для начинающих.\r\n2. При обнаружении ошибок обращайтесь к разработчикам системы через [официальный баг-трекер](http://code.google.com/p/molinos-cms/issues/list). Там же можно оставлять пожелания для следующих версий.\r\n3. Если вы не знаете, как сделать что-нибудь конкретное, и хотите проконсультироваться у более опытных разработчиков — воспользуйтесь [форумом](http://groups.google.com/group/molinos-cms).\r\n4. Чтобы перейти к административному интерфейсу, нажмите [сюда](?q=admin).\r\n5. И не забудьте настроить периодический запуск [планировщика заданий](?q=cron.rpc). Оптимальное время — раз в час.\r\n6. [mcms](http://molinos-cms.googlecode.com/) ♥ [xslt](http://ru.wikipedia.org/wiki/XSLT)\";}'),
 (20,'ru',NULL,'widget',NULL,NULL,NULL,'2008-06-25 12:10:23','2008-07-01 10:27:56',1,0,'doc',NULL,'a:3:{s:5:\"title\";s:31:\"Просмотр объекта\";s:9:\"classname\";s:9:\"DocWidget\";s:6:\"config\";a:2:{s:4:\"mode\";s:4:\"view\";s:5:\"fixed\";s:0:\"\";}}'),
 (21,'ru',9,'domain',2,3,NULL,'2008-06-25 12:11:39','2008-07-01 10:47:59',1,0,'node',NULL,'a:7:{s:7:\"aliases\";a:1:{i:0;s:0:\"\";}s:8:\"language\";s:2:\"ru\";s:12:\"content_type\";s:9:\"text/html\";s:6:\"params\";s:3:\"doc\";s:5:\"title\";s:31:\"Просмотр объекта\";s:5:\"theme\";s:7:\"example\";s:14:\"defaultsection\";s:2:\"10\";}'),
 (22,'ru',NULL,'widget',NULL,NULL,NULL,'2008-06-25 12:12:20','2008-07-01 10:38:45',1,0,'doclist',NULL,'a:3:{s:5:\"title\";s:33:\"Список документов\";s:9:\"classname\";s:10:\"ListWidget\";s:6:\"config\";a:4:{s:5:\"fixed\";s:0:\"\";s:12:\"fallbackmode\";s:0:\"\";s:5:\"limit\";s:2:\"10\";s:4:\"sort\";s:0:\"\";}}'),
 (27,'ru',NULL,'moduleinfo',NULL,NULL,NULL,'2008-06-27 15:46:25','2008-07-01 10:35:19',1,0,'tinymce',NULL,'a:1:{s:6:\"config\";a:4:{s:5:\"theme\";s:8:\"advanced\";s:4:\"gzip\";s:1:\"1\";s:7:\"toolbar\";s:7:\"topleft\";s:4:\"path\";s:6:\"bottom\";}}'),
 (29,'ru',NULL,'widget',NULL,NULL,NULL,'2008-07-01 10:16:05','2008-07-01 10:52:02',1,0,'sections',NULL,'a:3:{s:5:\"title\";s:40:\"Навигация по разделам\";s:9:\"classname\";s:10:\"MenuWidget\";s:6:\"config\";a:4:{s:5:\"fixed\";s:2:\"10\";s:5:\"depth\";s:1:\"7\";s:6:\"prefix\";s:0:\"\";s:6:\"header\";s:0:\"\";}}'),
 (34,'ru',NULL,'cronstats',NULL,NULL,NULL,'2009-02-12 11:52:52','2009-02-12 11:52:52',0,0,NULL,NULL,'a:0:{}');
