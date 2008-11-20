<?php

class Page
{
  public static function render(Context $ctx, $domain, $query)
  {
    if ($rpc = self::checkRPC($ctx, $query))
      return $rpc;

    // Находим страницу в структуре.
    if (false === ($data = Structure::getInstance()->findPage($domain, $query)))
      return false;

    // Устанавливаем распарсенные коды раздела и документа.
    if (!empty($data['args']['sec']))
      $ctx->section = $data['args']['sec'];
    if (!empty($data['args']['doc']))
      $ctx->document = $data['args']['doc'];

    if (!empty($data['page']['defaultsection']))
      $ctx->root = $data['page']['defaultsection'];

    if (!isset($ctx->section) and isset($ctx->root))
      $ctx->section = $ctx->root;

    // Устанавливаем шкуру.
    if (!empty($data['page']['theme']))
      $ctx->theme = $data['page']['theme'];

    // Находим виджеты для этой страницы.
    $widgets = array_key_exists('widgets', $data['page'])
      ? self::renderWidgets($ctx, $data['page']['widgets'])
      : array();

    $result = bebop_render_object('page', $data['name'], $ctx->theme, array(
      'widgets' => $widgets,
      ));

    $result = str_replace(
      array(
        '$execution_time',
        ),
      array(
        microtime(true) - MCMS_START_TIME,
        ),
      $result);

    $result = array(
      'headers' => array(
        'Content-Type: text/html; charset=utf-8',
        ),
      'content' => $result,
      );

    if ($ctx->debug('page'))
      mcms::debug($data, $widgets, $result);
    elseif ($ctx->debug('widget') and null === $ctx->get('widget')) {
      $result['content'] = "<html><body><h1>Отладка вджетов</h1><p>Выберите виджет:</p><ul>";

      ksort($widgets);

      $u = $ctx->url()->string();

      foreach ($widgets as $k => $v) {
        $result['content'] .= "<li><a href='{$u}&amp;widget={$k}'>{$k}</a></li>";
      }

      $result['content'] .= "</ul><hr/>"
        . mcms::getSignature($ctx)
        . "</body></html>";
    }

    $result['headers'][] = 'Content-Length: ' . strlen($result['content']);

    return $result;
  }

  private static function renderWidgets(Context $ctx, array $names)
  {
    $s = Structure::getInstance();

    $result = array();
    $target = $ctx->get('widget');

    foreach ($names as $region => $list) {
      foreach ($s->findWidgets($list) as $name => $widget) {
        if (null !== $target and $name !== $target)
          continue;

        if (class_exists($widget['class'])) {
          $o = new $widget['class']($name, $widget);
          $result[$name] = $o->render($ctx);
        } else {
          $result[$name] = "<!-- widget {$name} halted: class {$widget['class']} not found. -->";
        }
      }
    }

    return $result;
  }

  private static function checkRPC(Context $ctx, $query)
  {
    if ('admin' == $query or 0 === strpos($query, 'admin/'))
      $query = 'admin.rpc';

    elseif (strpos($query, 'attachment/') === 0)
      $query = 'attachment.rpc';

    if ('.rpc' == substr($query, -4)) {
      $module = substr($query, 0, -4);

      if ($ctx->method('post') and isset($ctx->db))
        $ctx->db->beginTransaction();

      $args = array($ctx);

      try {
        if (false === ($result = mcms::invoke_module($module, 'iRemoteCall', 'hookRemoteCall', $args)))
          throw new RuntimeException(t('Обработчик RPC в модуле %module отсутствует.', array(
            '%module' => $module,
            )));
      } catch (Exception $e) {
        mcms::fatal($e);
      }

      if ($ctx->method('post') and isset($ctx->db))
        $ctx->db->commit();

      if (!empty($result))
        return array('content' => $result);

      if (null !== ($next = $ctx->get('destination')))
        $ctx->redirect($next);

      header('HTTP/1.1 200 OK');
      header('Content-Type: text/plain; charset=utf-8');
      die('Request not handled.');
    }
  }
}
