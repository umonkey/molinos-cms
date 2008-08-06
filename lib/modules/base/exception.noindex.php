<?php
/**
 * Исключение: индекс не найден.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: индекс не найден.
 *
 * Возникает при попытке отфильтровать или отсортировать объекты по полю, по
 * которомы сортировать и фильтровать нелзя.
 *
 * @package mod_base
 * @subpackage Exceptions
 */
class NoIndexException extends UserErrorException
{
  public function __construct($name)
  {
    parent::__construct('Отсутствует индекс '. $name, 500, 'Отсутствует индекс', t('Выборка по полю %field невозможна: отсутствует индекс.', array('%field' => $name)));
  }
};
