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
    $domain = Structure::getinstance()->findPage($ctx->host(), '');

    if ('robots.txt' == $this->query) {
      if (is_array($domain) and !empty($domain['robots']))
        $robots = $domain['robots'];
      else
        $robots = DomainNode::getDefaultRobots();
      return new Response($robots, 'text/plain');
    }

    if (defined('MCMS_BENCHMARK'))
      mcms::flog(sprintf('%f = before xmlrouter started', microtime(true) - MCMS_START_TIME));

    $output = '';

    // Определяем шкуру.
    $theme = is_array($domain)
      ? $domain['page']['theme']
      : 'default';

    try {
      // Находим страницу в структуре.
      if (false === ($data = Structure::getInstance()->findPage($ctx->host(), $this->query)))
        throw new PageNotFoundException();

      if (!empty($data['page']['theme']))
        $theme = $data['page']['theme'];

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

      $this->checkParameters($ctx);

      $ctx->registry->broadcast('ru.molinos.cms.hook.request.before', array($ctx));

      // Устанавливаем шкуру.
      if (!empty($data['page']['theme']) and !isset($theme))
        $theme = $data['page']['theme'];

      // Находим виджеты для этой страницы.
      $time = microtime(true);
      $widgets = array_key_exists('widgets', $data['page'])
        ? self::renderWidgets($ctx, $data['page']['widgets'])
        : array();
      if (defined('MCMS_BENCHMARK'))
        mcms::flog(sprintf('%f = time(widgets)', microtime(true) - $time));

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

      $output .= html::em('widgets', join('', $widgets));
      $data['status'] = 200;
    }

    catch (Exception $e) {
      $code = ($e instanceof UserErrorException)
        ? $e->getCode()
        : 500;

      $data = array(
        'status' => $code,
        'name' => 'error-' . $code,
        'page' => array(
          'errorMessage' => $e->getMessage(),
          ),
        );
    }

    $output = $this->getRequestOptions($ctx) . $output
      . $ctx->getExtrasXML();

    $stylesheet = self::findStyleSheet($theme, $data['name']);

    $data['page']['status'] = $data['status'];
    $data['page']['name'] = $data['name'];
    $data['page']['base'] = $ctx->url()->getBase($ctx);
    $data['page']['prefix'] = os::webpath(MCMS_SITE_FOLDER, 'themes', $theme);
    $data['page']['execution_time'] = microtime(true) - MCMS_START_TIME;
    $data['page']['version'] = mcms::version();
    $data['page']['url'] = $ctx->url()->string();
    $data['page']['back'] = urlencode(MCMS_REQUEST_URI);
    $data['page']['debug'] = $ctx->canDebug();

    $result = '<?xml version="1.0" encoding="utf-8"?>';
    $result .= html::em('page', $data['page'], $output);

    if (defined('MCMS_BENCHMARK'))
      mcms::flog(sprintf('%f = before xslt::transform() in xmlrouter', microtime(true) - MCMS_START_TIME));

    $contentType = empty($data['page']['content_type'])
      ? 'text/html'
      : $data['page']['content_type'];

    return xslt::transform($result,
      self::findStyleSheet($theme, $data['name']), $contentType);
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

  private function checkParameters(Context $ctx)
  {
    $ids = array();

    // Собираем полученные идентификаторы.
    foreach (array('section', 'document', 'root') as $k)
      if ($id = $ctx->$k->id and !in_array($id, $ids))
        $ids[] = $ctx->$k->id;

    // Проверяем, все ли из них валидны.
    if (!empty($ids)) {
      $params = array();
      $count = $ctx->db->fetch($sql = "SELECT COUNT(*) FROM `node` WHERE `deleted` = 0 AND `published` = 1 AND `id` " . sql::in($ids, $params), $params);
      if (!$count)
        throw new PageNotFoundException();
    }

    // Если не указан раздел, но указан документ — имитируем указание раздела.
    if (!isset($ctx->section) and isset($ctx->document)) {
      if (count($tags = $ctx->db->getResultsV('id', "SELECT `n`.`id` AS `id` FROM `node` `n` INNER JOIN `node__rel` `l` ON `l`.`tid` = `n`.`id` WHERE `l`.`nid` = ? AND `n`.`deleted` = 0 AND `n`.`published` = 1 AND `n`.`class` = 'tag'", array($ctx->document->id)))) {
        if (is_numeric($want_section = $ctx->get('section')) and in_array($want_section, $tags))
          $id = $want_section;
        else
          list($id) = $tags;
        $ctx->section = intval($id);
      }
    }
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
          $time = microtime(true);

          try {
            $o = new $widget['class']($name, $widget);
            $result[$name] = $o->render($ctx);
          } catch (Exception $e) {
            $result[$name] = html::em('widget', array(
              'name' => $name,
              'error' => $e->getMessage(),
              'type' => get_class($e),
              ));
          }

          if (defined('MCMS_BENCHMARK'))
            mcms::flog(sprintf('%f = time(widget/%s)', microtime(true) - $time, $name));
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
