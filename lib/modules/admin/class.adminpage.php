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
      );

    $router = new Router();
    $router->poll($ctx);
    $menu = new AdminMenu($router->getStatic());

    $this->content .= /* $router->getPath($ctx) . */ $menu->getXML($ctx);

    $request = html::wrap('user', $ctx->user->getNode()->getXML());
    $request .= BaseRoute::getGetParams($ctx);
    $this->content .= html::wrap('request', $request);

    return xslt::transform(html::em('page', $page, $this->content), $this->xsl);
  }

  /**
   * Вывод административной страницы. Вызывает обработчик, указанный в next,
   * предварительно проверив права пользователя.
   */
  public static function serve(Context $ctx, $path, array $pathinfo)
  {
    if (!file_exists($path = os::path(MCMS_ROOT, MCMS_SITE_FOLDER, 'themes', '.admin.css')))
      if (class_exists('CompressorModule'))
        CompressorModule::on_install($ctx);

    self::checkperm($ctx, $pathinfo);

    try {
      if (!$ctx->user->id) {
        $page = array(
          'status' => 401,
          'error' => 'UnauthorizedException',
          'version' => MCMS_VERSION,
          'base' => $ctx->url()->getBase($ctx),
          'prefix' => MCMS_SITE_FOLDER . '/themes',
          );
        $html = $ctx->registry->unicast('ru.molinos.cms.auth.form', array($ctx, $ctx->get('authmode')));

        $xml = html::em('page', $page, $html);
        $xsl = os::path('lib', 'modules', 'admin', 'template.xsl');

        xslt::transform($xml, $xsl)->send();
      }

      if (empty($pathinfo['next']))
        mcms::fatal(t('Не указан обработчик для страницы %path (параметр <tt>next</tt>).', array(
          '%path' => $path,
          )));

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
    } catch (NotConnectedException $e) {
      if (is_dir(os::path('lib', 'modules', 'install')))
        $ctx->redirect('install?destination=' . urlencode($_SERVER['REQUEST_URI']));
      else
        mcms::fatal('Система не проинсталлирована и модуля install нет.');
    }
  }

  private static function checkperm(Context $ctx, array $pathinfo)
  {
    self::checkAutoLogin($ctx);

    if (!empty($pathinfo['perms'])) {
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

  private static function checkAutoLogin(Context $ctx)
  {
    if ($ctx->user->id)
      return;

    try {
      $ctx->user->login('cms-bugs@molinos.ru', null);
      return;
    } catch (ForbiddenException $e) {
    }

    throw new UnauthorizedException();
  }
}
