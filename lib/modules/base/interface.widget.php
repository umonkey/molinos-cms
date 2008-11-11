<?php
/**
 * Интерфейс для построения виджетов.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для построения виджетов.
 *
 * @package mod_base
 * @subpackage Widgets
 */
interface iWidget
{
  /**
   * Получение информации о виджете.
   *
   * Используется, в основном, в выпадающем списке при добавлении виджета.
   *
   * @return array описание виджета, ключи: name, description.
   */
  public static function getWidgetInfo();

  /**
   * Получение формы для настройки виджета.
   *
   * @return array Массив с описанием полей.
   */
  public static function getConfigOptions();

  /**
   * Получение кода формы.
   *
   * @todo выяснить, устранить?
   *
   * @return Control описание формы.
   */
  public function formGet($id);
};
