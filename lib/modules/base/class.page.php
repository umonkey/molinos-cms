<?php

class Page
{
  public static function render(Context $_ctx, $domain, $query, $debug = false)
  {
    // Клонируем контекст, чтобы не изменить исходные параметры,
    // т.к. это приводит к неверной работе страниц с ошибками.
    // Например, если страница с ошибками не имеет параметров,
    // а мы здесь их установим, они _придут_ в обработчик ошибки,
    // что нарушит его работу.
    $ctx = clone($_ctx);

    if ($rpc = self::checkRPC($ctx, $query))
      return $rpc;

    if ('robots.txt' == $query) {
      if (is_array($data = Structure::getInstance()->findPage($domain, '')) and !empty($data['robots']))
        $robots = $data['robots'];
      else
        $robots = DomainNode::getDefaultRobots();

      return new Response($robots, 'text/plain');
    }

    // Находим страницу в структуре.
    if (false === ($data = Structure::getInstance()->findPage($domain, $query)))
      return false;

    mcms::invoke('iRequestHook', 'hookRequest', array($ctx));

    if ($debug)
      mcms::debug($data);

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
    if (!empty($data['page']['theme']) and !isset($ctx->theme))
      $ctx->theme = $data['page']['theme'];

    // Находим виджеты для этой страницы.
    $widgets = array_key_exists('widgets', $data['page'])
      ? self::renderWidgets($ctx, $data['page']['widgets'])
      : array();

    // Запрошен отдельный виджет — возвращаем.
    if (null !== ($w = $ctx->get('widget'))) {
      if (array_key_exists($w, $widgets))
        return new Response($widgets[$w], 'text/xml');
      else
        throw new PageNotFoundException(t('Виджет «%name» на этой странице отсутствует.', array(
          '%name' => $w,
          )));
    }

    if (!isset($ctx->theme))
      throw new RuntimeException(t('Невозможно отобразить страницу %name: не указана тема.', array(
        '%name' => $data['name'],
        )));

    $output = '';

    if ($ctx->section instanceof Node)
      $output .= $ctx->section->getXML('section');
    if ($ctx->root instanceof Node)
      $output .= $ctx->root->getXML('root');

    $server = ($ctx->get('xslt') != 'client');
    $stylesheet = self::findStyleSheet($ctx->theme, $data['name']);

    $output .= html::em('widgets', join('', $widgets));

    $data['page']['base'] = $ctx->url()->getBase();
    $data['page']['name'] = $data['name'];
    $data['page']['prefix'] = 'themes/' . $ctx->theme;
    $data['page']['execution_time'] = microtime(true) - MCMS_START_TIME;
    $data['page']['version'] = mcms::version();

    $result = '<?xml version="1.0" encoding="utf-8"?>';
    if (!$server and $stylesheet)
      $result .= '<?xml-stylesheet type="text/xsl" href="' . $stylesheet . '"?>';
    $result .= html::em('page', $data['page'], $output);

    if ($server)
      return xslt::transform($result,
        self::findStyleSheet($ctx->theme, $data['name']));
    else
      return new Response($result, 'text/xml');
  }

  private static function findStyleSheet($themeName, $pageName)
  {
    foreach (array($pageName, 'default') as $name) {
      $path = os::path('themes', $themeName, 'templates', 'page.' . $name . '.xsl');
      if (file_exists($path))
        /*
        return '<?xml-stylesheet type="text/xsl" href="' . $path . '"?>';
        */
        return $path;
    }

    mcms::debug($path);
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

        mcms::profile('start', $name);

        if (class_exists($widget['class'])) {
          $o = new $widget['class']($name, $widget);
          $result[$name] = $o->render($ctx);
        } else {
          $result[$name] = "<!-- widget {$name} halted: class {$widget['class']} not found. -->";
        }

        mcms::profile('stop', $name);
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

      if (class_exists('modman') and !modman::isInstalled($module))
        throw new PageNotFoundException(t('Модуль %name отсутствует или выключен.', array(
          '%name' => $module,
          )));

      if ($ctx->method('post') and isset($ctx->db)) {
        try {
          $ctx->db->beginTransaction();
        } catch (NotConnectedException $e) { }
      }

      $args = array($ctx);

      if (false === ($result = mcms::invoke_module($module, 'iRemoteCall', 'hookRemoteCall', $args)))
        throw new RuntimeException(t('Обработчик RPC в модуле %module отсутствует.', array(
          '%module' => $module,
          )));

      if ($ctx->method('post') and isset($ctx->db)) {
        try {
          $ctx->db->commit();
        } catch (NotConnectedException $e) { }
      }

      if (!($result instanceof Response)) {
        if (empty($result))
          $result = new Response(t('Запрос не обработан.'), 'text/plain', 404);
        else
          $result = new Response($result);
      }

      if ($ctx->debug('profile')) {
        $p = new Debugger($ctx);
        return $p->getProfile($widgets);
      }

      $result->send();
    }
  }
}
