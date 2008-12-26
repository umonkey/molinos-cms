<?php
/**
 * Интерфейс для написания шаблонизаторов.
 *
 * @package mod_base
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для написания шаблонизаторов.
 *
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */
interface iTemplateProcessor
{
  /**
   * Возвращает список расширений файлов в виде массива.
   *
   * Используется для построения карты соответствия
   * расширений классам при "перезагрузке" системы.
   */
  public static function getExtensions();

  /**
   * Обрабатывает файл, возвращает результат.
   */
  public static function processTemplate($fileName, array $data);
}
