<?php
/**
 * Интерфейс для пост-/пре- обработки запросов.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Интерфейс для пост-/пре- обработки запросов.
 *
 * Позволяет выполнять код перед основной обработкой запроса.  Например для
 * того, чтобы какие-то запросы жёстко запретить.  Например для того, чтобы
 * реализовать дополнительную проверку прав на доступ к сайту.
 *
 * На данный момент используется модулем accesslog для сбора статистики.
 *
 * @package mod_base
 * @subpackage Core
 */
interface iRequestHook
{
  /**
   * Обработка запроса.
   *
   * @param RequestContext $ctx Описание контекста, если выполняется
   * пост-обработка, или NULL, если пре-модерация.
   */
  public static function hookRequest(RequestContext $ctx = null);
};
