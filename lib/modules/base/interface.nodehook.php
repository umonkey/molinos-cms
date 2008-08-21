<?php
/**
 * Интерфейс для отслеживания действий над объектами.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для отслеживания действий над объектами.
 *
 * Используется для выполнения различных задач, не имеющих прямого отношения к
 * объекту, например: индексирование для полнотекстового поиска, обычное
 * индексирование (@todo вынести из NodeBase::reindex()), удаление устаревших
 * ревизий, итд.
 *
 * @package mod_base
 * @subpackage Types
 */
interface iNodeHook
{
  /**
   * Дополнительная обработка действий над объектами.
   *
   * @param Node $node объект, над которым совершается действие.
   *
   * @param string $op Тип действия, варианты: create, edit, update, delete,
   * publish, unpublish.
   *
   * @return void
   */
  public static function hookNodeUpdate(Node $node, $op);
};
