<?php
/**
 * Маршрутизатор запросов в формате JSON.
 *
 * Содержит собственную обработку ошибок, чтобы всегда возвращать их
 * в JSON виде и не отдавать скриптам HTML страницы.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class JSONRouter implements iRequestRouter
{
  protected $query;

  public function __construct($query)
  {
    $this->query = substr($query, 0, -5);
  }

  public function route(Context $ctx)
  {
    $args = array($ctx);

    try {
      $response = mcms::invoke_module($this->query, 'iJSONHandler', 'hookJSONCall', $args);

      if (false === $response)
        throw new PageNotFoundException();

      if (!is_array($response))
        throw new RuntimeException(t('Обработчик запроса вернул %type вместо массива.', array(
          '%type' => is_object($response)
            ? get_class($response)
            : gettype($response),
          )));

      $response = array(
        'status' => 'ok',
        'code' => 200,
        'content' => $response,
        );
    } catch (UserErrorException $e) {
      $response = array(
        'status' => 'error',
        'code' => $e->getCode(),
        'class' => get_class($e),
        'content' => $e->getMessage(),
        );
    } catch (Exception $e) {
      $response = array(
        'status' => 'error',
        'code' => 500,
        'class' => get_class($e),
        'content' => $e->getMessage(),
        );
    }

    return new JSONResponse($response);
  }
}
