<?php
/**
 * Исключение: ошибка валидации.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: ошибка валидации.
 *
 * На данный момент используется лишь в нескольких случаях: при неудачном
 * изменении пароля (значения двух полей не совпадают) и при неверном угадывании
 * капчи.  В перспективе основным источником исключения будет валидатор форм
 * (выполняющий проверку введённых пользователем значений).
 *
 * @package mod_base
 * @subpackage Exceptions
 */
class ValidationException extends UserErrorException
{
  public function __construct($name, $message = null)
  {
    if ($message === null)
      $message = "Вы не заполнили поле &laquo;{$name}&raquo;, которое нужно заполнить обязательно.&nbsp; Пожалуйста, вернитесь назад и проверьте введённые данные.";

    parent::__construct("Ошибка ввода данных", 400, "Ошибка в поле <span class='highlight'>&laquo;{$name}&raquo;</span>", $message);
  }
};
