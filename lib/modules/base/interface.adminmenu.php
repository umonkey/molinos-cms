<?php
/**
 * Интерфейс для внедрения в административное меню.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для внедрения в административное меню.
 *
 * @package mod_base
 * @subpackage Core
 */
interface iAdminMenu
{
  /**
   * Получение списка команд.
   *
   * @return array Массив с описанием команд.  Каждый элемент — массив с
   * обязательными ключами: title (заголовок) и href (ссылка), опционально:
   * description (всплывающая подсказка), group (группа, варианты: "content",
   * "access", "schema", "structure").
   */
  public static function getMenuIcons();
};
