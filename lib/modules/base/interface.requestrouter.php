<?php

interface iRequestRouter
{
  /**
   * Инициализация маршрутизатора.
   */
  public function __construct($query);

  /**
   * Обработка запроса.
   *
   * @return Response результат обработки.
   */
  public function route(Context $ctx);
}
