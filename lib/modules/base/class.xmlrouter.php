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

    if (!isset($ctx->theme))
      throw new RuntimeException(t('Невозможно отобразить страницу %name: не указана тема.', array(
        '%name' => $data['name'],
        )));

    $output = $this->getRequestOptions($ctx);

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

  private function getRequestOptions(Context $ctx)
  {
    $output = $ctx->url()->getArgsXML();
    $output .= mcms::user()->getNode()->getXML('user');

    if ($ctx->section instanceof Node) {
      $output .= '<!-- requested section -->';
      $output .= $ctx->section->getXML('section');
    }
    if ($ctx->root instanceof Node) {
      $output .= '<!-- default section for this page -->';
      $output .= $ctx->root->getXML('root');
    }

    return html::em('request', array(
      'remoteIP' => $_SERVER['REMOTE_ADDR'],
      ), $output);
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

    return os::path('lib', 'modules', 'admin', 'template.xsl');
  }
}
