<?php
/**
 * Интерфейс для дополнительной обработки запросов.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для дополнительной обработки запросов.
 *
 * Используется для манипуляции кодом страницы перед его выдачей пользователю.
 * Используется, например, компрессором (modules/compressor) для удаления лишних
 * пустот из HTML.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */
interface iPageHook
{
  /**
   * Обработка результата шаблонизации.
   *
   * @param string &$output отдаваемый пользователю код.
   *
   * @param Node $page страница, в контексте которой выполняется запрос.
   *
   * @return void
   */
  public static function hookPage(&$output, Node $page);
};
