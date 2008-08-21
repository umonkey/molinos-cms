<?php
/**
 * Механизм для выполнения периодических задач.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Механизм для выполнения периодических задач.
 *
 * @package mod_base
 * @subpackage Core
 */
interface iScheduler
{
  /**
   * Выполнение задач.
   *
   * Вызывается модулем cron, который, в свою очередь, вызывается извне через
   * /cron.rpc.
   *
   * @return void
   */
  public static function taskRun();
};
