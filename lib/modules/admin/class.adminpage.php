<?php

class AdminPage
{
  private $xsl;

  public function __construct($content, $xsl = null)
  {
    if (null === $xsl)
      $xsl = os::path('lib', 'modules', 'admin', 'template.xsl');

    $this->content = $content;
    $this->xsl = $xsl;
  }

  public function getResponse(Context $ctx)
  {
    $page = array(
      'status' => 200,
      'base' => $ctx->url()->getBase($ctx),
      'host' => MCMS_HOST_NAME,
      'folder' => $ctx->folder(),
      'sitefolder' => os::webpath(MCMS_SITE_FOLDER),
      'prefix' => os::webpath(MCMS_SITE_FOLDER, 'themes'),
      'query' => $ctx->query(),
      'version' => defined('MCMS_VERSION')
        ? MCMS_VERSION
        : 'unknown',
      'cache' => cache::getInstance()->getName(),
      'memory' => ini_get('memory_limit'),
      'time' => microtime(true) - MCMS_START_TIME,
      'back' => urlencode(MCMS_REQUEST_URI),
      'next' => $ctx->get('destination'),
      'api' => 'cms://localhost/api/',
      );

    $router = new Router();
    $router->poll($ctx);
    $menu = new AdminMenu($router->getStatic());

    $this->content .= /* $router->getPath($ctx) . */ $menu->getXML($ctx);

    $request = '';
    if ($userid = $ctx->user->id)
      $request .= html::wrap('user', Node::findXML(array('id' => $userid), $ctx->db));
    $request .= $ctx->url()->getArgsXML();
    $this->content .= html::wrap('request', $request);

    return xslt::transform(html::em('page', $page, $this->content), $this->xsl);
  }

  /**
   * Вывод административной страницы. Вызывает обработчик, указанный в next,
   * предварительно проверив права пользователя.
   */
  public static function serve(Context $ctx, $path, array $pathinfo)
  {
    if (class_exists('APIStream'))
      APIStream::init($ctx);

    if (!file_exists(os::path(MCMS_ROOT, MCMS_SITE_FOLDER, 'themes', '.admin.css')))
      if (class_exists('CompressorModule'))
        CompressorModule::on_install($ctx);

    try {
      self::checkperm($ctx, $pathinfo);

      if (!$ctx->user->id) {
        $page = array(
          'status' => 401,
          'error' => 'UnauthorizedException',
          'version' => MCMS_VERSION,
          'base' => $ctx->url()->getBase($ctx),
          'prefix' => MCMS_SITE_FOLDER . '/themes',
          'back' => $_SERVER['REQUEST_URI'],
          );

        $html = $ctx->registry->unicast('ru.molinos.cms.auth.form', array($ctx, $ctx->get('authmode')));

        $xml = html::em('page', $page, $html);
        $xsl = os::path('lib', 'modules', 'admin', 'template.xsl');

        xslt::transform($xml, $xsl)->send();
      }

      if (empty($pathinfo['next'])) {
        if (!empty($pathinfo['xsl']))
          $pathinfo['next'] = 'AdminPage::xsltonly';
        else
          mcms::fatal(t('Не указан обработчик для страницы %path (параметр <tt>next</tt>).', array(
            '%path' => $path,
            )));
      }

      if (!is_callable($pathinfo['next']))
        mcms::fatal(t('Неверный обработчик для страницы %path (<tt>%next()</tt>).', array(
          '%path' => $path,
          '%next' => $pathinfo['next'],
          )));

      $args = func_get_args();
      $output = call_user_func_array($pathinfo['next'], $args);

      if (!($output instanceof Response)) {
        $xsl = empty($pathinfo['xsl'])
          ? null
          : implode(DIRECTORY_SEPARATOR, explode('/', $pathinfo['xsl']));
        $tmp = new AdminPage($output, $xsl);
        $output = $tmp->getResponse($ctx);
      }

      return $output;
    }

    catch (NotConnectedException $e) {
      if (is_dir(os::path('lib', 'modules', 'install')))
        $ctx->redirect('install?destination=' . urlencode($_SERVER['REQUEST_URI']));
      else
        mcms::fatal('Система не проинсталлирована и модуля install нет.');
    }

    catch (Exception $e) {
      $data = array(
        'status' => 500,
        'error' => get_class($e),
        'message' => $e->getMessage(),
        'version' => MCMS_VERSION,
        'release' => MCMS_RELEASE,
        'base' => $ctx->url()->getBase($ctx),
        'prefix' => MCMS_SITE_FOLDER . '/themes',
        'back' => urlencode($_SERVER['REQUEST_URI']),
        'next' => urlencode($ctx->get('destination')),
        'clean' => !empty($_GET['__cleanurls']),
        );

      if ($e instanceof UserErrorException)
        $data['status'] = $e->getCode();

      $xsl = os::path('lib', 'modules', 'admin', 'template.xsl');
      xslt::transform(html::em('page', $data), $xsl)->send();
    }
  }

  /**
   * Пустой обработчик для случаев, когда достаточно шаблона.
   */
  private static function xsltonly(Context $ctx)
  {
    return html::em('content', array(
      'mode' => 'custom',
      ));
  }

  public static function checkperm(Context $ctx, array $pathinfo)
  {
    if (!empty($pathinfo['perms'])) {
      if (!$ctx->user->id)
        throw new UnauthorizedException();
      if ('debug' == $pathinfo['perms'])
        $result = $ctx->canDebug();
      else {
        list($mode, $type) = explode(',', $pathinfo['perms']);
        $result = $ctx->user->hasAccess($mode, $type);
      }
      if (!$result)
        throw new ForbiddenException();
    }
  }
}
