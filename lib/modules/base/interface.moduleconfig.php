<?php
/**
 * Интерфейс для настраиваемых модулей.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для настраиваемых модулей.
 *
 * @package mod_base
 * @subpackage Controls
 */
interface iModuleConfig
{
  /**
   * Получение формы для настройки модуля.
   *
   * @return Control компоненты формы, обычно FieldSet.
   */
  public static function formGetModuleConfig();
};
