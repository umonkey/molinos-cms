<?php
/**
 * Исключение: объект не найден.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: объект не найден.
 *
 * Возникает при попытке загрузить несуществующую ноду.
 *
 * @package mod_base
 * @subpackage Exceptions
 *
 * @see NodeBase::find()
 */
class ObjectNotFoundException extends UserErrorException
{
  public function __construct()
  {
    parent::__construct("Объект не найден", 404, "Объект не найден", "Вы попытались обратиться к объекту, который не удалось найти.");
  }
};
