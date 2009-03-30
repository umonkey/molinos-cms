<?php
/**
 * Маршрутизатор запросов к Molinos CMS.
 *
 * Выбирает нужный обработчик, вызывает нужные классы.  При возникновении
 * ошибок пытается обработать запрос к странице errors/$code.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class Request
{
  public function __construct()
  {
  }

  public function process(Context $ctx)
  {
    $ctx->registry->broadcast('ru.molinos.cms.hook.request.before', array($ctx));

    $query = $ctx->query();

    try {
      if (null === ($router = $this->getRequestRouter($query)))
        throw new RuntimeException(t('Не удалось найти обработчик запроса: %query.', array(
          '%query' => $query,
          )));

      if (!($router instanceof iRequestRouter))
        throw new RuntimeException(t('Класс %class не является маршрутизатором запросов.', array(
          '%class' => get_class($router),
          )));

      ob_start();

      if (!(($response = $router->route($ctx))) instanceof Response)
        throw new RuntimeException(t('%class::route() вернул %type, а не объект класса Response.', array(
          '%type' => is_object($response)
            ? get_class($response)
            : gettype($response),
          '%class' => get_class($router),
          )));

      ob_end_clean();
    } catch (Exception $e) {
      mcms::fatal($e);
    }

    return $response;
  }

  /**
   * Подбирает обработчик для текущего запроса.
   */
  protected function getRequestRouter($query)
  {
    if ('admin' == $query or 0 === strpos($query, 'admin/'))
      $query = 'admin.rpc';

    if (0 === strpos($query, 'attachment/'))
      $query = 'attachment.rpc';

    if ($ext = substr($query, strrpos($query, '.') + 1))
      if (class_exists($className = strtoupper($ext) . 'Router'))
        return new $className($query);

    return new XMLRouter($query);
  }
}
