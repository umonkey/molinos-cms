<?php
/**
 * Скрипт для обновления файлов module.ini
 *
 * Не требует никаких параметров.  Анализирует все PHP файлы,
 * добавляет информацию о них в module.ini соответствующего
 * модуля.  Попутно сканирует информацию об обработчиках сообщений,
 * указанную в комментариях с префиксом @mcms_message.
 */

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'client.inc';

Context::last()->registry->rebuildMeta();

die("OK\n");
