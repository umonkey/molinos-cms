<?php

/**
 * Класс, отвечающий за вывод формы авторизации.
 */
class AdminAuth
{
  public static function get_login_form(Context $ctx, $path, array $pathinfo)
  {
    self::checkAutoLogin($ctx);

    if ($ctx->user->id)
      return $ctx->getRedirect();

    $form = $ctx->registry->unicast('ru.molinos.cms.auth.form', array($ctx, $ctx->get('authmode')));

    $page = array(
      'status' => 401,
      'error' => 'UnauthorizedException',
      'version' => MCMS_VERSION,
      'base' => $ctx->url()->getBase($ctx),
      'prefix' => MCMS_SITE_FOLDER . '/themes',
      );

    $xml = html::em('page', $page, $form);

    xslt::transform($xml, $pathinfo['xsl'])->send();
  }

  /**
   * Попытка войти с помощью стандартного логина.
   */
  private static function checkAutoLogin(Context $ctx)
  {
    if ($ctx->user->id)
      return;

    try {
      $ctx->user->login('cms-bugs@molinos.ru', null);
    } catch (ForbiddenException $e) { }
  }
}
