<?php

class OpenIdModule extends RPCHandler
{
  /**
   * @mcms_message ru.molinos.cms.rpc.openid
   */
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

  protected static function rpc_get_openid(Context $ctx)
  {
    $openidinfo = $ctx->get('openid');

    try {
      $ctx->db->beginTransaction();
      $node = self::openIDAuthorize($openidinfo['mode'], $ctx);
      mcms::session('uid', $node->id);
      mcms::session()->save();
      $ctx->db->commit();

      $ctx->redirect($ctx->get('destination', ''));
    } catch (ObjectNotFoundException $e) {
      throw new ForbiddenException(t('Вы успешно авторизировались, '
        .'но пользоваться сайтом не можете, т.к. прозрачная регистрация '
        .'пользователей OpenID отключена. Соболезнования.'));
    }

    $ctx->redirect("?q=admin&openid=failed");
  }

  protected static function rpc_post_login(Context $ctx)
  {
    self::OpenIDVerify($ctx->post('name'));
    exit();
  }

  public static function openIDAuthorize($openid_mode, Context $ctx)
  {
    self::includeOpenID();

    if ('none' == ($mode = $ctx->modconf('openid', 'mode', 'open')))
      throw new RuntimeException(t('Поддержка OpenID отключена администратором.'));

    if ('id_res' == $openid_mode) {
      $openid = null;

      foreach (array('openid1_claimed_id', 'openid_claimed_id', 'openid_identity') as $key) {
        if (!empty($_GET[$key])) {
          $openid = $_GET[$key];
          break;
        }
      }

      if (null === $openid)
        throw new RuntimeException('OpenID провайдер не вернул идентификатор.');

      $nodes = Node::find($ctx->db, array(
        'class' => 'user',
        'name' => $openid,
        'deleted' => 0,
        ));

      if (!count($nodes)) {
        if ('open' != $mode)
          throw new ForbiddenException(t('Извините, автоматическая регистрация пользователей через OpenID отключена.'));

        $fieldmap = array(
          'sreg_email' => 'email',
          'sreg_fullname' => 'fullname',
          'sreg_nickname' => 'nickname',
          );

        $node = Node::create('user', array(
          'parent_id' => null,
          'name' => $openid,
          'published' => true,
          ));

        foreach ($fieldmap as $k => $v) {
          if (!empty($_GET[$key = 'openid_'. $k]))
            $node->$v = $_GET[$key];
        }

        $node->setRegistered($ctx);
        $node->save();
      } else {
        $node = array_shift($nodes);

        if (!$node->published)
          throw new ForbiddenException(t('Ваш профиль заблокирован.'));
      }

      return $node;
    } else {
      // Login canceled
      mcms::flog('login cancelled ?!');
      $ctx->redirect("?q=user.rpc&action=logout");
    }
  }

  public static function OpenIDVerify($openid)
  {
    self::includeOpenID();
    $consumer = self::getConsumer();

    // Begin the OpenID authentication process.
    // No auth request means we can't begin OpenID.

    if (!($auth_request = $consumer->begin($openid))) {
      mcms::fatal(t('Не удалось соединиться с провайдером OpenID, '
        .'попробуйте повторить попытку позже.'));

      $r = new Redirect('?q=user.rpc&action=logout');
      $r->send();
    }

    $sreg_request = Auth_OpenID_SRegRequest::build(
      array('nickname'), // Required
      array('fullname', 'email') // Optional
      );

    if ($sreg_request)
      $auth_request->addExtension($sreg_request);

    $policy_uris = empty($_GET['policies']) ? null : $_GET['policies'];

    $pape_request = new Auth_OpenID_PAPE_Request($policy_uris);

    if ($pape_request)
      $auth_request->addExtension($pape_request);

    // Redirect the user to the OpenID server for authentication.
    // Store the token for this authentication so we can verify the
    // response.

    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
    // form to send a POST request to the server.
    if ($auth_request->shouldSendRedirect()) {
      $redirect_url = $auth_request->redirectURL(self::getTrustRoot(), self::getReturnTo($openid));
      if (Auth_OpenID::isFailure($redirect_url)) {
        // If the redirect URL can't be built, display an error message.
        mcms::flog("Could not redirect to server: " . $redirect_url->message);
      } else {
        // Send redirect.
        $r = new Redirect($redirect_url);
        $r->send();
      }
    } else {
      // Generate form markup and render it.
      $form_id = 'openid_message';
      $form_html = $auth_request->formMarkup(self::getTrustRoot(), self::getReturnTo($openid), false, array('id' => $form_id));

      // Display an error if the form markup couldn't be generated;
      // otherwise, render the HTML.
      if (Auth_OpenID::isFailure($form_html)) {
        mcms::flog("Could not redirect to server: " . $form_html->message);
      } else {
        $page_contents = array(
          "<html><head><title>",
          "OpenID transaction in progress",
          "</title></head>",
          "<body onload='document.getElementById(\"".$form_id."\").submit()'>",
          $form_html,
          "</body></html>");

        print implode("\n", $page_contents);
      }
    }
  }

  private static function getStore()
  {
    /**
     * This is where the example will store its OpenID information.
     * You should change this path if you want the example store to be
     * created elsewhere.  After you're done playing with the example
     * script, you'll have to remove this directory manually.
     */
    $store_path = os::mkdir($path = os::path(Context::last()->config->getPath('tmpdir'), 'openid'), 'Could not create the FileStore directory (%path), please check the effective permissions.', array(
      '%path' => $path,
      ));
    return new Auth_OpenID_FileStore($store_path);
  }

  private static  function getConsumer()
  {
    /**
     * Create a consumer object using the store object created
     * earlier.
     */
    $store = self::getStore();
    return new Auth_OpenID_Consumer($store);
  }

  private static function getReturnTo($id = null)
  {
    $url = sprintf('http://%s%s/?q=openid.rpc&action=openid&id=%s&sid=%s',
      $_SERVER['HTTP_HOST'], mcms::path(), urlencode($id), mcms::session()->id);

    if (!empty($_GET['destination']))
      $url .= '&destination='. urlencode($_GET['destination']);

    return mcms::fixurl($url);
  }

  function getScheme()
  {
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on')
      $scheme .= 's';
    return $scheme;
  }

  private static function getTrustRoot()
  {
   return sprintf("%s://%s:%s/", self::getScheme(), $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
  }

  private static function includeOpenID()
  {
    $path_extra = dirname(__FILE__);
    $path = ini_get('include_path');
    $path = $path_extra . PATH_SEPARATOR . $path;
    ini_set('include_path', $path);

    require_once "Auth/OpenID/Consumer.php";
    require_once "Auth/OpenID/FileStore.php";
    require_once "Auth/OpenID/SReg.php";
    require_once "Auth/OpenID/PAPE.php";
  }
}
