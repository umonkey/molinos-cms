<?php

class OpenIdProvider
{
  public static function openIDAuthorize($openid_mode)
  {
    self::includeOpenID();

    if ('none' == ($mode = mcms::modconf('auth', 'mode', 'open')))
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

      if (!count($nodes = Node::find(array('class' => 'user', 'name' => $openid)))) {
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

        $node->save();
      } else {
        $node = Node::load(array('class' => 'user', 'name' => $openid));
      }

      // Это хак. Нужен, чтобы тут bebop_split_url при вызове из RPCHandler
      // всегда гарантированно вернул NULL и мы во второй раз не свалились в
      // BaseModule. В случае с livejournal всё проходит нормально,
      // ($_SERVER['REQUEST_URI'] в данной точке сам по себе пуст), однако с
      // myopenid.com, видимо из-за того, что он использует автосабмит формы,
      // он остаётся не пустым.
      //unset($_SERVER['REQUEST_URI']);
      return $node;
    } else {
      // Login canceled
      mcms::log('openid', 'login cancelled ?!');
      mcms::redirect("base.rpc?action=logout");
    }
  }

  public static function OpenIDVerify($openid)
  {
    self::includeOpenID();
    $consumer = self::getConsumer();

    // Begin the OpenID authentication process.
    // No auth request means we can't begin OpenID.

    if (!($auth_request = $consumer->begin($openid)))
      mcms::redirect("base.rpc?action=logout");

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
        mcms::log('openid', "Could not redirect to server: " . $redirect_url->message);
      } else {
        // Send redirect.
        mcms::redirect($redirect_url);
      }
    } else {
      // Generate form markup and render it.
      $form_id = 'openid_message';
      $form_html = $auth_request->formMarkup(self::getTrustRoot(), self::getReturnTo($openid), false, array('id' => $form_id));

      // Display an error if the form markup couldn't be generated;
      // otherwise, render the HTML.
      if (Auth_OpenID::isFailure($form_html)) {
        mcms::log('openid', "Could not redirect to server: " . $form_html->message);
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
    $store_path = mcms::mkdir(mcms::config('tmpdir') .'/openid', 'Could not create the FileStore directory (%path), please check the effective permissions.');
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
    $url = sprintf('http://%s%s/?q=base.rpc&action=openid&id=%s',
      $_SERVER['HTTP_HOST'], mcms::path(), urlencode($id));

    return $url;
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
   // return l('/', null, null, true);
   return sprintf("%s://%s:%s/",
                   self::getScheme(), $_SERVER['SERVER_NAME'],
                   $_SERVER['SERVER_PORT']);
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
