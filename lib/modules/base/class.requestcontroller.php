<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class RequestController
{
  private $ctx;

  private $page = null;
  private $widgets = array();

  private $get_vars = array();

  private $context = null;

  private $root = null;

  /**
   * Базовая настройка обработки запроса.
   *
   * Определяет контекст, если он не указан; больше ничего не делает.
   */
  public function __construct(Context $ctx = null)
  {
    if (null === $ctx)
      $ctx = new Context();

    $this->ctx = $ctx;
  }

  /**
   * Обработка запроса.
   *
   * Вызывает один из методов onGet(), onPost() итд; если обработчик не найден —
   * бросает исключение BadRequest.  Если произошло обращение к RPC или
   * специальной странице (на данный момент — только "admin"), вместо этих
   * методов вызывается обработчик RPC.
   *
   * Перед обработкой вызывается iRequestHook::hookRequest() с указанием
   * контекста.
   */
  public function run()
  {
    header('Content-Type: text/html; charset=utf-8');

    if (null !== ($result = $this->checkRPC()))
      return $result;

    $method = 'on'. ucfirst(strtolower($this->ctx->method()));

    if (method_exists($this, $method)) {
      mcms::invoke('iRequestHook', 'hookRequest', array($this->ctx));
      return call_user_func(array($this, $method));
    } else {
      throw new BadRequestException(t('Метод %method не поддерживается.',
        array('%method' => $this->ctx->method())), 405);
    }
  }

  /**
   * Обработка запросов методом GET.
   *
   * Находит нужную типовую страницу и вызывает её рендеринг.
   *
   * @return string результат для выдачи пользователю.
   */
  protected function onGet()
  {
    return $this->locatePage($this->ctx)
      ->render($this->ctx);
  }

  /**
   * Заглушка для обработчика POST.
   *
   * Выводит сообщение о том, что все формы нужно слать на RPC.
   */
  protected function onPost()
  {
    throw new BadRequestException(t('Формы и другие запросы методом POST '
      .'нужно отправлять на RPC (имя_модуля.rpc).'));
  }

  private function locatePage(Context $ctx)
  {
    $ids = $path = array();
    $domain = $ctx->locateDomain($ctx);

    // При обращении к главной странице экономим на подгрузке детей.
    if (null !== $ctx->query()) {
      $domain->loadChildren();

      if (false !== $ctx->query())
        $ids = explode('/', trim($ctx->query(), '/'));
      else
        $ids = array();

      while (!empty($ids)) {
        $found = false;

        if (is_array($domain->children)) {
          foreach ($domain->children as $page) {
            if ($ids[0] == $page->name) {
              $found = true;
              $domain = $page;
              $path[] = array_shift($ids);
              break;
            }
          }
        }

        // Если подходящая страница не найдена — прерываем поиск, $ids
        // содержат потенциальные идентификаторы объектов.
        if (!$found)
          break;
      }
    }

    if (empty($path))
      $domain->template_name = 'index';
    else
      $domain->template_name = join('-', $path);

    $this->setContextObjectIds($ctx, $domain, $ids);

    return $domain;
  }

  private function setContextObjectIds(Context $ctx, DomainNode $page, array $ids)
  {
    $sec = null;
    $doc = null;

    $load = array();

    switch ($page->params) {
    case 'sec':
      $sec = array_shift($ids);
      break;
    case 'doc':
      $doc = array_shift($ids);
      break;
    case 'sec+doc':
      $sec = array_shift($ids);
      $doc = array_shift($ids);
      break;
    }

    if (empty($sec) and is_numeric($page->defaultsection))
      $sec = $page->defaultsection;

    if (!empty($sec))
      $load[] = $sec;
    if (!empty($doc))
      $load[] = $doc;

    // Остался мусор в урле — страница не найдена.
    if (!empty($ids))
      throw new PageNotFoundException();

    // Загружаем объекты.
    if (!empty($load)) {
      $nodes = Node::find(array(
        'id' => $load,
        'published' => 1,
        ));

      // Найдено не всё: сообщаем об ошибке.
      if (count($nodes) != count($load)) {
        $message = null;

        if (null !== $sec and !array_key_exists($sec, $nodes))
          $message = t('Раздел не найден.');
        elseif (null !== $doc and !array_key_exists($doc, $nodes))
          $message = t('Объект не найден.');

        throw new PageNotFoundException($message);
      }

      // Записываем объекты в контекст.
      if (null !== $sec)
        $ctx->section = $nodes[$sec];
      if (null !== $doc)
        $ctx->document = $nodes[$doc];
    }
  }

  private function checkRPC()
  {
    $q = $this->ctx->query();

    if ('admin' == $q or 0 === strpos($q, 'admin/'))
      $q = 'admin.rpc';

    elseif (strpos($q, 'attachment/') === 0)
      $q = 'attachment.rpc';

    if ('.rpc' == substr($q, -4)) {
      $module = substr($q, 0, -4);

      if ($this->ctx->method('post'))
        mcms::db()->beginTransaction();

      $args = array($this->ctx);

      if (false === ($result = mcms::invoke_module($module, 'iRemoteCall', 'rpc_'. $this->ctx->get('action'), $args)))
        $result = mcms::invoke_module($module, 'iRemoteCall', 'hookRemoteCall', $args);

      if ($this->ctx->method('post'))
        mcms::db()->commit();

      if (false !== $result)
        return $result;

      if (null !== ($next = $this->ctx->get('destination')))
        $this->ctx->redirect($next);

      header('HTTP/1.1 200 OK');
      header('Content-Type: text/plain; charset=utf-8');
      die('Request not handled.');
    }
  }
}
