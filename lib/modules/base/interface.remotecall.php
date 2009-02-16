<?php
/**
 * Интерфейс для обработки "удалённых вызовов" (RPC).
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для обработки "удалённых вызовов" (RPC).
 *
 * Суть "удалённых вызовов" в том, что они обрабатываются в отрыве от страниц и
 * виджетов.  Точка входа всегда одна: /имя_модуля.rpc, все внешние параметры
 * доступны обработчику.
 *
 * @package mod_base
 * @subpackage Core
 */
interface iRemoteCall
{
  /**
   * Обработка удалённого вызова.
   *
   * @param Context $ctx описание контекста.
   *
   * @return Response результат работы или Redirect. Если ничего не вернулось,
   * редиректит происходит на адрес, указанный в параметре destination.
   */
  public static function hookRemoteCall(Context $ctx, $className);
};
