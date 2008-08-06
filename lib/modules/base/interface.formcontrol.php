<?php
/**
 * Интерфейс для взаимодействия с элементами форм (контролами).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для взаимодействия с элементами форм (контролами).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */
interface iFormControl
{
  /**
   * Возвращает описание элемента.
   *
   * Возвращаемый массив может содержать ключи: name, description, hidden.
   *
   * @return array
   */
  public static function getInfo();

  /**
   * Возвращает SQL-код для индексирования данных.
   *
   * Возвращает SQL-код, пригодный для описания поля таблицы,
   * в котором можно хранить данные, с которыми работает этот
   * элемент.  Если вернулся NULL — данные не индексируются.
   */
  public static function getSQL();

  /**
   * Возвращает HTML код элемента.
   */
  public function getHTML(array $data);

  /**
   * Проверка введённых пользователем данных.
   *
   * В случае ошибки кидает ValidationException.
   *
   * @return void
   * @param array $data содержимое полученной от пользователя формы.
   */
  public function validate(array $data);

  /**
   * Добавление дочернего контрола.
   *
   * @return void
   */
  public function addControl(Control $ctl);

  /**
   * Поиск вложенного контрола.
   *
   * Рекурсивно обходит групповые вложенные контролы.
   * Если ничего не найдено — возвращает NULL.
   *
   * @return Control
   * @param string $value имя контрола (его параметр value).
   */
  public function findControl($value);
};
