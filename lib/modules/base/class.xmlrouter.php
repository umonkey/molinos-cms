<?php

class XMLRouter implements iRequestRouter
{
  protected $query;

  public function __construct($query)
  {
    $this->query = $query;
  }

  public function route(Context $ctx)
  {
    if ('robots.txt' == $this->query) {
      if (is_array($data = Structure::getInstance()->findPage($ctx->host(), '')) and !empty($data['robots']))
        $robots = $data['robots'];
      else
        $robots = DomainNode::getDefaultRobots();

      return new Response($robots, 'text/plain');
    }

    // Находим страницу в структуре.
    if (false === ($data = Structure::getInstance()->findPage($ctx->host(), $this->query)))
      throw new PageNotFoundException();

    mcms::invoke('iRequestHook', 'hookRequest', array($ctx));

    // Устанавливаем распарсенные коды раздела и документа.
    if (!empty($data['args']['sec']))
      if (!isset($ctx->section))
        $ctx->section = $data['args']['sec'];
    if (!empty($data['args']['doc']))
      if (!isset($ctx->document))
        $ctx->document = $data['args']['doc'];

    if (!empty($data['page']['defaultsection']) and !isset($ctx->root))
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
      if (array_key_exists($w, $widgets)) {
        $output = '<?xml version="1.0" encoding="utf-8"?>'
          . $widgets[$w];
        return new Response($output, 'text/xml');
      } else {
        throw new PageNotFoundException(t('Виджет «%name» на этой странице отсутствует.', array(
          '%name' => $w,
          )));
      }
    }

    $theme = isset($ctx->theme)
      ? $ctx->theme
      : 'default';

    $output = $this->getRequestOptions($ctx);
    $output .= NodeStub::getStack('nodes');

    $stylesheet = self::findStyleSheet($ctx->theme, $data['name']);

    $output .= html::em('widgets', join('', $widgets));

    $data['page']['base'] = $ctx->url()->getBase($ctx);
    $data['page']['name'] = $data['name'];
    $data['page']['prefix'] = os::webpath(MCMS_SITE_FOLDER, 'themes', $ctx->theme);
    $data['page']['execution_time'] = microtime(true) - MCMS_START_TIME;
    $data['page']['version'] = mcms::version();

    $result = '<?xml version="1.0" encoding="utf-8"?>';
    $result .= html::em('page', $data['page'], $output);

    return xslt::transform($result,
      self::findStyleSheet($ctx->theme, $data['name']));
  }

  private function getRequestOptions(Context $ctx)
  {
    $output = $ctx->url()->getArgsXML();

    $attrs = array(
      'remoteIP' => $_SERVER['REMOTE_ADDR'],
      );

    if (null !== ($tmp = Context::last()->user->getNode()))
      $output .= $tmp->push('user');

    if (null !== ($tmp = $ctx->section))
      $output .= $tmp->push('section');

    if (null !== ($tmp = $ctx->root))
      $output .= $tmp->push('root');

    return html::em('request', $attrs, $output);
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

  private static function findStyleSheet($themeName, $pageName)
  {
    if (!is_dir($prefix = os::path(MCMS_SITE_FOLDER, 'themes', $themeName)))
      $prefix = os::path(MCMS_SITE_FOLDER, 'themes', 'default');

    foreach (array($pageName, 'default') as $name) {
      $path = os::path($prefix, 'templates', 'page.' . $name . '.xsl');
      if (file_exists($path))
        return $path;
    }
  }
}
