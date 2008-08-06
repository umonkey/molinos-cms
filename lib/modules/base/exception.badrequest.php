<?php
/**
 * Исключение: неверный запрос.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: неверный запрос.
 *
 * Для клиента это — ошибка 400, «запрос к серверу сформулирован неверно».
 *
 * @package mod_base
 * @subpackage Exceptions
 *
 * @todo выяснить область применения.
 */
class BadRequestException extends UserErrorException
{
  public function __construct()
  {
    parent::__construct("Неверный запрос", 400, "Неверный запрос", "Запрос к серверу сформулирован неверно.");
  }
};
