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
   * @return Control Описание формы или NULL, если настройки не нужны.  Форма
   * обычно начинается с группового компонента FieldSet (базовая реализация
   * Widget::formGetConfig() возвращает пустой FieldSet).
   */
  public static function formGetConfig();

  /**
   * Получение конфигурации виджета.
   *
   * @todo выяснить, что к чему.  Устранить?
   *
   * @return void
   */
  public function formHookConfigData(array &$data);

  /**
   * Обработка сохранения настроек виджета.
   *
   * Использовался в основном для создания необходимых виджету таблиц и типов
   * документов, сейчас для этого есть другие средства.
   *
   * @todo устранить.
   *
   * @return void
   */
  public function formHookConfigSaved();

  /**
   * Получение кода формы.
   *
   * @todo выяснить, устранить?
   *
   * @return Control описание формы.
   */
  public function formGet($id);
};
