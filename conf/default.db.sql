SET NAMES utf8;
DROP TABLE IF EXISTS `node`; CREATE TABLE `node` (`id` int NOT NULL, `lang` char(4) NOT NULL, `rid` int NULL, `parent_id` int NULL, `class` varchar(16) NOT NULL, `code` varchar(16) NULL, `left` int NULL, `right` int NULL, `uid` int NULL, `created` datetime NULL, `updated` datetime NULL, `published` tinyint(1) NOT NULL DEFAULT 0, `deleted` tinyint(1) NOT NULL DEFAULT 0);
DROP TABLE IF EXISTS `node__rev`; CREATE TABLE `node__rev` (`rid` integer NOT NULL PRIMARY KEY AUTO_INCREMENT, `nid` int NULL, `uid` int NULL, `name` varchar(255) NULL, `data` mediumblob NULL, `created` datetime NOT NULL, `name_lc` VARCHAR(255) NULL);
DROP TABLE IF EXISTS `node__idx_file`; CREATE TABLE `node__idx_file` (`id` int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT, `filename` VARCHAR(255) NOT NULL, `filetype` VARCHAR(255) NOT NULL, `filesize` DECIMAL(10,2) NOT NULL, `filepath` VARCHAR(255) NOT NULL);
DROP TABLE IF EXISTS `node__rel`; CREATE TABLE `node__rel` (`nid` int NOT NULL, `tid` int NOT NULL, `key` varchar(255) NULL, `order` int NULL);
DROP TABLE IF EXISTS `node__access`; CREATE TABLE `node__access` (`nid` int NOT NULL, `uid` int NOT NULL, `c` tinyint(1) NOT NULL DEFAULT 0, `r` tinyint(1) NOT NULL DEFAULT 0, `u` tinyint(1) NOT NULL DEFAULT 0, `d` tinyint(1) NOT NULL DEFAULT 0, `p` tinyint(1) NOT NULL DEFAULT 0);
DROP TABLE IF EXISTS `node__session`; CREATE TABLE `node__session` (`sid` char(32) NOT NULL PRIMARY KEY, `created` datetime NOT NULL, `data` blob NOT NULL);
DROP TABLE IF EXISTS `node__seq`; CREATE TABLE `node__seq` (`id` INTEGER PRIMARY KEY AUTO_INCREMENT, `n` int NULL);
DROP TABLE IF EXISTS `node__fallback`; CREATE TABLE `node__fallback` (`old` varchar(255) NOT NULL, `new` varchar(255) NULL, `ref` varchar(255) NULL);
CREATE INDEX `IDX_node_id` on `node` (`id`);
CREATE INDEX `IDX_node_lang` on `node` (`lang`);
CREATE INDEX `IDX_node_class` on `node` (`class`);
CREATE INDEX `IDX_node_code` on `node` (`code`);
CREATE INDEX `IDX_node_left` on `node` (`left`);
CREATE INDEX `IDX_node_right` on `node` (`right`);
CREATE INDEX `IDX_node_uid` on `node` (`uid`);
CREATE INDEX `IDX_node_created` on `node` (`created`);
CREATE INDEX `IDX_node_updated` on `node` (`updated`);
CREATE INDEX `IDX_node_published` on `node` (`published`);
CREATE INDEX `IDX_node_deleted` on `node` (`deleted`);
CREATE INDEX `IDX_node__rev_nid` on `node__rev` (`nid`);
CREATE INDEX `IDX_node__rev_uid` on `node__rev` (`uid`);
CREATE INDEX `IDX_node__rev_name` on `node__rev` (`name`);
CREATE INDEX `IDX_node__rev_created` on `node__rev` (`created`);
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
CREATE INDEX `IDX_node__session_created` on `node__session` (`created`);
CREATE INDEX `IDX_node__rev_name_lc` on `node__rev` (`name_lc`);
CREATE INDEX `IDX_node__fallback_old` on `node__fallback` (`old`);
CREATE UNIQUE INDEX `IDX_node__access_key` ON `node__access` (`nid`, `uid`);
CREATE UNIQUE INDEX `IDX_node_rid` ON `node` (`rid`);
INSERT INTO `node` (`id`,`lang`,`rid`,`parent_id`,`class`,`code`,`left`,`right`,`uid`,`created`,`updated`,`published`,`deleted`) VALUES
 (2,'ru',1,NULL,'type',NULL,NULL,NULL,NULL,'2008-04-07 12:15:40','2008-06-03 15:17:16',1,0),
 (3,'ru',134,NULL,'type',NULL,NULL,NULL,8,'2008-04-07 12:15:41','2008-11-17 15:37:54',1,0),
 (4,'ru',3,NULL,'type',NULL,NULL,NULL,NULL,'2008-04-07 12:15:41','2008-06-03 15:17:16',1,0),
 (5,'ru',129,NULL,'type',NULL,NULL,NULL,8,'2008-04-07 12:15:41','2008-08-21 14:27:59',1,0),
 (6,'ru',5,NULL,'type',NULL,NULL,NULL,NULL,'2008-04-07 12:15:41','2008-06-03 15:17:16',1,0),
 (8,'ru',7,NULL,'user',NULL,NULL,NULL,NULL,'2008-04-07 12:15:43','2008-06-03 15:17:17',1,0),
 (9,'ru',90,NULL,'domain',NULL,1,4,NULL,'2008-04-07 12:15:46','2008-07-01 10:47:53',1,0),
 (10,'ru',135,NULL,'tag',NULL,5,10,18,'2008-04-07 12:58:18','2008-11-17 16:25:02',1,0),
 (11,'ru',10,NULL,'type',NULL,NULL,NULL,61,'2008-04-07 13:20:16','2008-06-03 15:17:17',1,0),
 (12,'ru',11,NULL,'moduleinfo',NULL,NULL,NULL,18,'2008-04-08 10:06:52','2008-06-03 15:17:17',0,0),
 (13,'ru',18,NULL,'group',NULL,NULL,NULL,43,'2008-04-10 13:03:46','2008-06-23 13:06:50',1,0),
 (14,'ru',13,NULL,'type',NULL,NULL,NULL,NULL,'2008-04-30 18:52:04','2008-06-03 15:17:17',1,0),
 (15,'ru',17,NULL,'group',NULL,NULL,NULL,NULL,'2008-04-30 19:23:58','2008-06-23 13:06:45',1,0),
 (16,'ru',15,NULL,'type',NULL,NULL,NULL,NULL,'2008-05-06 15:35:06','2008-06-03 15:17:17',1,0),
 (17,'ru',16,NULL,'moduleinfo',NULL,NULL,NULL,NULL,'2008-05-06 17:06:15','2008-06-03 15:17:17',1,0),
 (18,'ru',132,NULL,'type',NULL,NULL,NULL,8,'2008-07-01 10:22:54','2008-11-17 15:34:58',1,0),
 (19,'ru',133,NULL,'article',NULL,NULL,NULL,8,'2008-06-25 12:09:57','2008-11-17 15:35:04',1,0),
 (20,'ru',75,NULL,'widget',NULL,NULL,NULL,NULL,'2008-06-25 12:10:23','2008-07-01 10:27:56',1,0),
 (21,'ru',91,9,'domain',NULL,2,3,NULL,'2008-06-25 12:11:39','2008-07-01 10:47:59',1,0),
 (22,'ru',83,NULL,'widget',NULL,NULL,NULL,NULL,'2008-06-25 12:12:20','2008-07-01 10:38:45',1,0),
 (25,'ru',98,10,'tag','',6,7,NULL,'2008-06-27 15:36:39','2008-07-01 11:45:21',1,0),
 (26,'ru',99,10,'tag','',8,9,NULL,'2008-06-27 15:39:58','2008-07-01 11:45:27',1,0),
 (27,'ru',80,NULL,'moduleinfo',NULL,NULL,NULL,NULL,'2008-06-27 15:46:25','2008-07-01 10:35:19',1,0),
 (29,'ru',92,NULL,'widget',NULL,NULL,NULL,NULL,'2008-07-01 10:16:05','2008-07-01 10:52:02',1,0),
 (32,'ru',126,NULL,'article',NULL,NULL,NULL,NULL,'2008-07-01 10:43:09','2008-07-02 16:07:40',1,0),
 (33,'ru',121,NULL,'article',NULL,NULL,NULL,NULL,'2008-07-01 10:43:09','2008-07-02 15:58:00',1,0);
INSERT INTO `node__rev` (`rid`,`nid`,`uid`,`name`,`data`,`created`,`name_lc`) VALUES
 (1,2,NULL,'type','a:3:{s:11:\"description\";s:60:\"Скелет структуры типа документа.\";s:5:\"title\";s:25:\"Тип документа\";s:6:\"fields\";a:5:{s:4:\"name\";a:6:{s:5:\"label\";s:27:\"Внутреннее имя\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:172:\"Может содержать только буквы латинского алфавита, арабские цифры и символ подчёркивания («_»).\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:5:\"title\";a:6:{s:5:\"label\";s:25:\"Название типа\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:410:\"Короткое, максимально информативное описание название документа.&nbsp; Хорошо: &laquo;Статья&raquo;, &laquo;Баннер 85x31&raquo;, плохо: &laquo;Текстовый документ для отображения на сайте&raquo;, &laquo;Баннер справа сверху под тем другим баннером&raquo;.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:5:{s:5:\"label\";s:16:\"Описание\";s:4:\"type\";s:15:\"TextAreaControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:6:\"notags\";a:6:{s:5:\"label\";s:43:\"Не работает с разделами\";s:4:\"type\";s:11:\"BoolControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:8:\"hasfiles\";a:6:{s:5:\"label\";s:34:\"Работает с файлами\";s:4:\"type\";s:11:\"BoolControl\";s:11:\"description\";s:106:\"Показывать вкладку для прикрепления произвольных файлов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}}}','2008-06-03 15:17:16',NULL),
 (3,4,NULL,'file','a:4:{s:11:\"description\";s:85:\"Используется для наполнения файлового архива.\";s:5:\"title\";s:8:\"Файл\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:7:{s:4:\"name\";a:6:{s:5:\"label\";s:27:\"Название файла\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:137:\"Человеческое название файла, например: &laquo;Финансовый отчёт за 2007-й год&raquo;\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:8:\"filename\";a:7:{s:5:\"label\";s:31:\"Оригинальное имя\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:543:\"Имя, которое было у файла, когда пользователь добавлял его на сайт.&nbsp; Под этим же именем файл будет сохранён, если пользователь попытается его сохранить.&nbsp; Рекомендуется использовать только латинский алфавит: Internet Explorer некорректно обрабатывает кириллицу в именах файлов при скачивании файлов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:8:\"filetype\";a:7:{s:5:\"label\";s:11:\"Тип MIME\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:185:\"Используется для определения способов обработки файла.&nbsp; Проставляется автоматически при закачке.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:8:\"filesize\";a:7:{s:5:\"label\";s:28:\"Размер в байтах\";s:4:\"type\";s:13:\"NumberControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:8:\"filepath\";a:7:{s:5:\"label\";s:41:\"Локальный путь к файлу\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:5:\"width\";a:5:{s:5:\"label\";s:12:\"Ширина\";s:4:\"type\";s:13:\"NumberControl\";s:11:\"description\";s:88:\"Проставляется только для картинок и SWF объектов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:6:\"height\";a:5:{s:5:\"label\";s:12:\"Высота\";s:4:\"type\";s:13:\"NumberControl\";s:11:\"description\";s:88:\"Проставляется только для картинок и SWF объектов.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}','2008-06-03 15:17:16',NULL),
 (5,6,NULL,'group','a:4:{s:11:\"description\";s:68:\"Используется для управления правами.\";s:5:\"title\";s:39:\"Группа пользователей\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:2:{s:4:\"name\";a:6:{s:5:\"label\";s:16:\"Название\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:5:{s:5:\"label\";s:16:\"Описание\";s:4:\"type\";s:15:\"TextAreaControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}','2008-06-03 15:17:16',NULL),
 (7,8,NULL,'cms-bugs@molinos.ru','a:1:{s:8:\"fullname\";s:22:\"Разработчик\";}','2008-06-03 15:17:17',NULL),
 (10,11,61,'moduleinfo','a:4:{s:11:\"description\";s:105:\"Используется системой для хранения информации о модулях.\";s:5:\"title\";s:37:\"Конфигурация модуля\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:3:{s:4:\"name\";a:6:{s:5:\"label\";s:18:\"Заголовок\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:7:\"created\";a:5:{s:5:\"label\";s:25:\"Дата создания\";s:4:\"type\";s:15:\"DateTimeControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:3:\"uid\";a:5:{s:5:\"label\";s:10:\"Автор\";s:4:\"type\";s:15:\"NodeLinkControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:9:\"user.name\";}}}','2008-06-03 15:17:17',NULL),
 (11,12,18,'exchange',NULL,'2008-06-03 15:17:17',NULL),
 (13,14,NULL,'user','a:4:{s:5:\"title\";s:39:\"Профиль пользователя\";s:11:\"adminmodule\";s:5:\"admin\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:4:{s:4:\"name\";a:6:{s:5:\"label\";s:19:\"Email или OpenID\";s:4:\"type\";s:12:\"EmailControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:8:\"fullname\";a:5:{s:5:\"label\";s:19:\"Полное имя\";s:4:\"type\";s:15:\"TextLineControl\";s:11:\"description\";s:143:\"Используется в подписях к комментариям, при отправке почтовых сообщений и т.д.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:8:\"password\";a:6:{s:5:\"label\";s:12:\"Пароль\";s:4:\"type\";s:15:\"PasswordControl\";s:11:\"description\";s:0:\"\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:5:\"email\";a:5:{s:5:\"label\";s:5:\"Email\";s:4:\"type\";s:12:\"EmailControl\";s:11:\"description\";s:47:\"Для доставки уведомлений.\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}','2008-06-03 15:17:17',NULL),
 (15,16,NULL,'domain','a:3:{s:5:\"title\";s:31:\"Типовая страница\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:9:{s:4:\"name\";a:3:{s:5:\"label\";s:6:\"Имя\";s:4:\"type\";s:15:\"TextLineControl\";s:8:\"required\";b:1;}s:5:\"title\";a:3:{s:5:\"label\";s:18:\"Заголовок\";s:4:\"type\";s:15:\"TextLineControl\";s:8:\"required\";b:1;}s:9:\"parent_id\";a:2:{s:5:\"label\";s:37:\"Родительский объект\";s:4:\"type\";s:11:\"EnumControl\";}s:7:\"aliases\";a:3:{s:5:\"label\";s:12:\"Алиасы\";s:4:\"type\";s:15:\"TextAreaControl\";s:11:\"description\";s:115:\"Список дополнительных адресов, по которым доступен этот домен.\";}s:8:\"language\";a:5:{s:5:\"label\";s:8:\"Язык\";s:4:\"type\";s:11:\"EnumControl\";s:11:\"description\";s:100:\"Язык для этой страницы, используется только шаблонами.\";s:7:\"options\";a:3:{s:2:\"ru\";s:14:\"русский\";s:2:\"en\";s:20:\"английский\";s:2:\"de\";s:16:\"немецкий\";}s:8:\"required\";b:1;}s:5:\"theme\";a:3:{s:5:\"label\";s:10:\"Шкура\";s:4:\"type\";s:11:\"EnumControl\";s:11:\"description\";s:73:\"Имя папки с шаблонами для этой страницы.\";}s:12:\"content_type\";a:4:{s:5:\"label\";s:23:\"Тип контента\";s:4:\"type\";s:11:\"EnumControl\";s:8:\"required\";b:1;s:7:\"options\";a:2:{s:9:\"text/html\";s:4:\"HTML\";s:8:\"text/xml\";s:3:\"XML\";}}s:6:\"params\";a:4:{s:5:\"label\";s:37:\"Разметка параметров\";s:4:\"type\";s:11:\"EnumControl\";s:8:\"required\";b:1;s:7:\"options\";a:4:{s:0:\"\";s:27:\"без параметров\";s:7:\"sec+doc\";s:31:\"/раздел/документ/\";s:3:\"sec\";s:14:\"/раздел/\";s:3:\"doc\";s:18:\"/документ/\";}}s:14:\"defaultsection\";a:2:{s:5:\"label\";s:29:\"Основной раздел\";s:4:\"type\";s:11:\"EnumControl\";}}}','2008-06-03 15:17:17',NULL),
 (16,17,NULL,'auth','a:1:{s:6:\"config\";a:4:{s:4:\"mode\";s:4:\"open\";s:14:\"profile_fields\";a:3:{i:0;s:5:\"email\";i:1;s:8:\"fullname\";i:2;s:8:\"language\";}s:11:\"groups_anon\";a:1:{i:0;s:1:\"6\";}s:6:\"groups\";a:1:{i:0;s:1:\"6\";}}}','2008-06-03 15:17:17',NULL),
 (17,15,NULL,'Администраторы','a:2:{s:11:\"description\";s:50:\"Могут работать с контентом.\";s:5:\"login\";s:28:\"Администраторы\";}','2008-06-23 13:06:45',NULL),
 (18,13,43,'Разработчики','a:2:{s:11:\"description\";s:70:\"Пользователи из этой группы могут всё.\";s:5:\"login\";s:24:\"Разработчики\";}','2008-06-23 13:06:50',NULL),
 (75,20,NULL,'doc','a:3:{s:5:\"title\";s:31:\"Просмотр объекта\";s:9:\"classname\";s:9:\"DocWidget\";s:6:\"config\";a:2:{s:4:\"mode\";s:4:\"view\";s:5:\"fixed\";s:0:\"\";}}','2008-07-01 10:27:56',NULL),
 (80,27,NULL,'tinymce','a:1:{s:6:\"config\";a:4:{s:5:\"theme\";s:8:\"advanced\";s:4:\"gzip\";s:1:\"1\";s:7:\"toolbar\";s:7:\"topleft\";s:4:\"path\";s:6:\"bottom\";}}','2008-07-01 10:35:19',NULL),
 (83,22,NULL,'doclist','a:3:{s:5:\"title\";s:33:\"Список документов\";s:9:\"classname\";s:10:\"ListWidget\";s:6:\"config\";a:4:{s:5:\"fixed\";s:0:\"\";s:12:\"fallbackmode\";s:0:\"\";s:5:\"limit\";s:2:\"10\";s:4:\"sort\";s:0:\"\";}}','2008-07-01 10:38:45',NULL),
 (90,9,NULL,'localhost','a:7:{s:5:\"title\";s:11:\"Molinos.CMS\";s:8:\"language\";s:2:\"ru\";s:12:\"content_type\";s:9:\"text/html\";s:7:\"aliases\";a:1:{i:0;s:0:\"\";}s:6:\"params\";s:3:\"sec\";s:5:\"theme\";s:7:\"example\";s:14:\"defaultsection\";s:2:\"10\";}','2008-07-01 10:47:53',NULL),
 (91,21,NULL,'node','a:7:{s:7:\"aliases\";a:1:{i:0;s:0:\"\";}s:8:\"language\";s:2:\"ru\";s:12:\"content_type\";s:9:\"text/html\";s:6:\"params\";s:3:\"doc\";s:5:\"title\";s:31:\"Просмотр объекта\";s:5:\"theme\";s:7:\"example\";s:14:\"defaultsection\";s:2:\"10\";}','2008-07-01 10:47:59',NULL),
 (92,29,NULL,'sections','a:3:{s:5:\"title\";s:40:\"Навигация по разделам\";s:9:\"classname\";s:10:\"MenuWidget\";s:6:\"config\";a:4:{s:5:\"fixed\";s:2:\"10\";s:5:\"depth\";s:1:\"7\";s:6:\"prefix\";s:0:\"\";s:6:\"header\";s:0:\"\";}}','2008-07-01 10:52:02',NULL),
 (98,25,NULL,'Что такое веб-сайт?','a:1:{s:11:\"description\";s:119:\"Из чего состоит сайт? Что такое: контент, шаблоны, виджеты, модули?\";}','2008-07-01 11:45:21',NULL),
 (99,26,NULL,'Как собрать сайт?','a:1:{s:11:\"description\";s:82:\"Первые шаги в создании сайта с помощью Molinos.CMS\";}','2008-07-01 11:45:27',NULL),
 (121,33,NULL,'Как собрать сайт?','a:1:{s:4:\"text\";s:1335:\"<p>Последовательность действий обычно такая:</p>\r\n<ol>\r\n<li>Описать структуру данных, построив <a href=\"?q=admin&amp;mode=tree&amp;preset=taxonomy&amp;cgroup=content\">дерево разделов</a>.</li>\r\n<li>Настроить <a href=\"?q=admin&amp;mode=list&amp;preset=schema&amp;cgroup=structure\">типы документов</a>, привязать каждый тип к нужным разделам.</li>\r\n<li><a href=\"?q=admin&amp;mode=create&amp;type=article&amp;destination=%2Fsites%2Fumonkey%2Fadmin%3Fmode%3Dlist%26columns%3Dname%252Cclass%252Cuid%252Ccreated%26cgroup%3Dcontent&amp;cgroup=content\">Создать несколько документов</a> с какой-нибудь &laquo;<a href=\"http://vesna.yandex.ru/\">рыбой</a>&raquo;.</li>\r\n<li>Создать нужные <a href=\"?q=admin&amp;mode=tree&amp;preset=pages&amp;cgroup=structure\">домены и типовые страницы</a>.</li>\r\n<li>Создать <a href=\"?q=admin&amp;mode=list&amp;preset=widgets&amp;cgroup=structure\">виджеты</a>, прикрепить их к нужным страницам.</li>\r\n<li>Создать <a href=\"http://code.google.com/p/molinos-cms/wiki/Templating\">шаблоны для виджетов и страниц</a>.</li>\r\n</ol>\";}','2008-07-02 15:58:00',NULL),
 (126,32,NULL,'Что такое веб-сайт?','a:1:{s:4:\"text\";s:5950:\"<p>Для пользователя веб-сайт &mdash; это набор страниц, объединённых общим доменным именем, однако если взглянуть на любой современный сайт изнутри &mdash; это не так. &nbsp;Современные сайты содержат огромное количество доступной информации, для управления которой и нужны <strong>с</strong>истемы <strong>у</strong>правления <strong>к</strong>онтентом, вроде Molinos.CMS.</p>\r\n<p>Любая современная CMS позволяет отделить наполнение (<em>что</em> будет показано пользователю) от представления (<em>как</em> это будет показано), и Molinos.CMS &mdash; не исключение. &nbsp;Вот из чего состоит сайт в нашем случае:</p>\r\n<ol>\r\n<li><strong>Документы</strong>. &nbsp;Они могут быть произвольных типов: статьи, новости, товары &mdash; вы сами <a href=\"?q=admin&amp;mode=list&amp;preset=schema&amp;cgroup=structure\">описываете документы</a>, с которыми работаете. &nbsp;У каждого типа документа могут быть свои уникальные атрибуты, например, у статей всегда есть заголовок и текст, у товара &mdash; картинка и цена. &nbsp;Molinos.CMS поставляется с некоторыми наиболее популярными заготовками, но вы можете перенастроить их на свой лад.</li>\r\n<li><strong>Разделы</strong>. &nbsp;Из них формируется <em><a href=\"?q=admin&amp;mode=tree&amp;preset=taxonomy&amp;cgroup=content\">логическая структура</a></em> сайта &mdash; иерархия данных. &nbsp;Структура может быть любой сложности: ни количество разделов, ни степень их вложенности не ограничены. &nbsp;Каждый документ может быть помещён в любой раздел (или сразу в несколько), что добавляет структуре гибкости.&nbsp;</li>\r\n</ol>\r\n<p>Это &mdash; всё, что касается данных; функциональную структуру сайта формируют другие компоненты:</p>\r\n<ol>\r\n<li><strong><a href=\"?q=admin&amp;mode=tree&amp;preset=pages&amp;cgroup=structure\">Домены</a></strong>. &nbsp;Одна инсталляция Molinos.CMS может работать с любым их количеством. &nbsp;Каждый домен может быть совершенно отдельным сайтом, а может использоваться для быстрого доступа к части данных (например, spb.news.ru &mdash; часть гипотетического новостного сайта, имеющая отношение к Санкт-Петербургу).</li>\r\n<li><strong>Типовые страницы</strong>. &nbsp;Это &mdash; шаблоны страницы, сгруппированные по функциональности. &nbsp;Например, у главной страницы обычно отдельный шаблон, у страницы со списком новостей &mdash; отдельный (с возможностью быстрого поиска) и у страницы с полным текстом новости &mdash; отдельный (с возможностью эту новость комментировать). &nbsp;Это &mdash; три разные <em>типовые страницы</em>. &nbsp;Их не обязательно использовать: встречаются люди, которые предпочитают один большой шаблон со сложными условиями, но разные шаблоны, всё таки, проще и удобнее.</li>\r\n<li><strong><a href=\"?q=admin&amp;mode=list&amp;preset=widgets&amp;cgroup=structure\">Виджеты</a></strong>. &nbsp;Это &mdash; кирпичи, из которых строятся страницы. &nbsp;Каждый виджет выполняет одну простую задачу, например &mdash; выводит меню, список документов, текст документа, форму для добавления комментария, содержимое корзины или код счётчика для Google Analytics. &nbsp;У каждой <em>типовой страницы</em> может быть свой набор виджетов, из чего и формируется её собственная функциональность.</li>\r\n<li><strong><a href=\"?q=admin&amp;mode=modules&amp;cgroup=structure\">Модули</a></strong>. &nbsp;Это сгруппированные по функциональности классы для PHP, которые могут быть подключены или отключены в процессе работы сайта, для изменения его функциональности. &nbsp;Molinos.CMS в базовой поставке содержит всё, что нужно большинству сайтов, однако при необходимости вы можете реализовать <em>любую</em> специфическую функциональность, ознакомившись с <a href=\"http://molinos-cms.googlecode.com/\">технической докумментацией</a>.</li>\r\n</ol>\";}','2008-07-02 16:07:40',NULL),
 (129,5,8,'tag','a:5:{s:11:\"description\";s:76:\"Разделы определяют структуру информации.\";s:5:\"title\";s:23:\"Раздел сайта\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:2:{s:4:\"name\";a:4:{s:5:\"label\";s:21:\"Имя раздела\";s:4:\"type\";s:15:\"TextLineControl\";s:10:\"dictionary\";s:6:\"widget\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:3:{s:5:\"label\";s:16:\"Описание\";s:4:\"type\";s:15:\"TextAreaControl\";s:10:\"dictionary\";s:6:\"widget\";}}s:14:\"prev_published\";i:1;}','2008-08-21 14:27:59',NULL),
 (132,18,8,'article','a:4:{s:6:\"fields\";a:4:{s:4:\"name\";a:9:{s:5:\"label\";s:18:\"Заголовок\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:15:\"textlinecontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";s:7:\"indexed\";s:1:\"1\";}s:4:\"text\";a:8:{s:5:\"label\";s:10:\"Текст\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:15:\"texthtmlcontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:6:\"teaser\";a:7:{s:5:\"label\";s:12:\"Превью\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:15:\"texthtmlcontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}s:8:\"sections\";a:7:{s:5:\"label\";s:44:\"Опубликовать в разделах\";s:5:\"group\";s:14:\"Разделы\";s:11:\"description\";s:0:\"\";s:4:\"type\";s:15:\"sectionscontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}s:5:\"title\";s:12:\"Статья\";s:14:\"prev_published\";i:1;s:11:\"description\";s:161:\"Используется для формирования статичных страниц: информация о сайте, карта проезда итд.\";}','2008-11-17 15:34:58','article'),
 (133,19,8,'Установка завершена','a:1:{s:4:\"text\";s:1576:\"<p>Поздравляем, вы успешно установили Molinos.CMS.</p>\r\n<p>Что вам нужно знать:</p>\r\n<ol>\r\n<li>Если вы уже знакомы с этой CMS, вы можете перейти к <a href=\"?q=admin\">административному интерфейсу</a> прямо сейчас (сохраните эту ссылку). &nbsp;Изначально система работает без административного пароля, однако вы можете его установить, для обеспечения безопасности.</li>\r\n<li>Ссылки в левой части страницы &mdash; это встроенная документация. &nbsp;Она содержит вводную информацию для новичков, ознакомившись с которой вам будет гораздо проще создать свой собственный сайт, чем делать это вслепую, &laquo;методом тыка&raquo;.</li>\r\n<li>Встроенная документация оформлена в виде обычного сайта, на котором можно ставить эксперименты.</li>\r\n<li>Если в процессе что-нибудь сломалось &mdash; просто удалите файл <code>conf/default.db</code>, и начните сначала (если, конечно, вы используете SQLite; с другими серверами БД всё не так просто).</li>\r\n</ol>\";}','2008-11-17 15:35:04','установка завершена'),
 (134,3,8,'widget','a:4:{s:11:\"description\";s:73:\"Базовый блок для формирования страницы.\";s:5:\"title\";s:12:\"Виджет\";s:6:\"notags\";s:1:\"1\";s:6:\"fields\";a:3:{s:4:\"name\";a:8:{s:5:\"label\";s:27:\"Внутреннее имя\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:178:\"Используется для идентификации виджета внутри шаблонов, а также для поиска шаблонов для виджета.\";s:4:\"type\";s:15:\"textlinecontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:5:\"title\";a:8:{s:5:\"label\";s:16:\"Название\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:57:\"Человеческое название виджета.\";s:4:\"type\";s:15:\"textlinecontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";s:8:\"required\";s:1:\"1\";}s:11:\"description\";a:7:{s:5:\"label\";s:16:\"Описание\";s:5:\"group\";s:33:\"Основные свойства\";s:11:\"description\";s:135:\"Краткое описание выполняемых виджетом функций и особенностей его работы.\";s:4:\"type\";s:15:\"textareacontrol\";s:10:\"dictionary\";s:6:\"widget\";s:7:\"default\";s:0:\"\";s:6:\"values\";s:0:\"\";}}}','2008-11-17 15:37:54','widget'),
 (135,10,18,'Molinos.CMS','a:1:{s:11:\"description\";s:129:\"Это — начало структуры вашего сайта.  Все подразделы добавляются сюда.\";}','2008-11-17 16:25:02','molinos.cms');
INSERT INTO `node__rel` (`nid`,`tid`,`key`,`order`) VALUES
 (8,7,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,9,NULL,NULL),
 (8,13,NULL,1),
 (30,10,NULL,2),
 (31,18,NULL,2),
 (22,18,NULL,3),
 (31,9,NULL,2),
 (20,9,NULL,3),
 (22,9,NULL,4),
 (20,21,NULL,1),
 (29,9,NULL,5),
 (29,21,NULL,2),
 (33,26,NULL,1),
 (32,25,NULL,1),
 (19,10,NULL,3);
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
 (18,15,1,1,1,1,1),
 (18,13,1,1,1,1,1),
 (3,15,0,1,0,0,0),
 (3,13,1,1,1,1,1),
 (10,15,1,0,0,0,0),
 (25,15,1,0,0,0,0),
 (26,15,1,0,0,0,0),
 (10,13,1,0,0,0,0),
 (25,13,1,0,0,0,0),
 (26,13,1,0,0,0,0);
