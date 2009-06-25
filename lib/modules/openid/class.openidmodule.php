<?php

class OpenIdModule extends RPCHandler
{
  /**
   * Запуск авторизации с помощью OpenID.
   * 
   * @param Context $ctx 
   * @param array $params 
   * @return mixed
   * @mcms_message ru.molinos.cms.auth.process.openid
   */
  public static function on_auth_start(Context $ctx, array $params)
  {
    if (array_key_exists($params['type'], $map = self::getProviders()))
      $params['id'] = str_replace('!', $params['id'], $map[$params['type']]['rewrite']);

    self::OpenIDVerify($params['id']);
    exit();
  }

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
      User::storeSessionData($node->id);
      $ctx->db->commit();

      $ctx->redirect($ctx->get('destination', ''));
    } catch (ObjectNotFoundException $e) {
      throw new ForbiddenException(t('Вы успешно авторизировались, '
        .'но пользоваться сайтом не можете, т.к. прозрачная регистрация '
        .'пользователей OpenID отключена. Соболезнования.'));
    }

    $ctx->redirect("?q=admin&openid=failed");
  }

  public static function openIDAuthorize($openid_mode, Context $ctx)
  {
    self::includeOpenID();

    $mode = $ctx->config->get('modules/openid/mode', 'open');

    if ('none' == $mode)
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

      $nodes = Node::find(array(
        'class' => 'user',
        'name' => $openid,
        'deleted' => 0,
        ), $ctx->db);

      if (!count($nodes)) {
        if ('open' != $mode)
          throw new ForbiddenException(t('Извините, автоматическая регистрация пользователей через OpenID отключена.'));

        $fieldmap = array(
          'sreg_email' => 'email',
          'sreg_fullname' => 'fullname',
          'sreg_nickname' => 'nickname',
          );

        $node = Node::create(array(
          'class' => 'user',
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
      Logger::log('login cancelled ?!');
      $ctx->redirect("?q=auth.rpc&action=logout");
    }
  }

  public static function OpenIDVerify($openid)
  {
    self::includeOpenID();
    $consumer = self::getConsumer();

    // Begin the OpenID authentication process.
    // No auth request means we can't begin OpenID.

    if (!($auth_request = $consumer->begin($openid))) {
      throw new RuntimeException(t('Не удалось соединиться с провайдером OpenID, '
        .'попробуйте повторить попытку позже.'));

      $r = new Redirect('?q=auth.rpc&action=logout');
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
        Logger::log("Could not redirect to server: " . $redirect_url->message);
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
        Logger::log("Could not redirect to server: " . $form_html->message);
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
    $store_path = os::mkdir($path = os::path(Context::last()->config->getPath('main/tmpdir'), 'openid'), 'Could not create the FileStore directory (%path), please check the effective permissions.', array(
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
      MCMS_HOST_NAME, mcms::path(), urlencode($id), mcms::session()->id);

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

  /**
   * Возвращает информацию о себе как о провайдере.
   * 
   * @param Context $ctx 
   * @return array
   * @mcms_message ru.molinos.cms.auth.enum
   */
  public static function on_enum_providers()
  {
    $list = array(
      'standard' => 'OpenID',
      );
    foreach (self::getProviders() as $k => $v)
      $list[$k] = $v['name'];

    $schema = new Schema(array(
      'type' => array(
        'type' => 'EnumControl',
        'label' => t('Тип провайдера'),
        'required' => true,
        'options' => $list,
        'default' => 'standard',
        ),
      'id' => array(
        'type' => 'TextLineControl',
        'label' => t('Ваш идентификатор'),
        'required' => true,
        'description' => t('Если вы вводите полный адрес, включая http://, тип значения не имеет.'),
        ),
      ));

    return array('openid', t('Войти с помощью OpenID'), $schema);
  }

  /**
   * Возвращает информацию о провайдерах.
   */
  protected static function getProviders()
  {
    return array(
      // http://info.diary.ru/index.php?title=OpenID
      'diary' => array(
        'name' => 'Diary.ru',
        'rewrite' => 'http://!.diary.ru/',
        ),
      'livejournal' => array(
        'name' => 'Live Journal',
        'rewrite' => 'http://!.livejournal.com/',
        ),
      // http://www.livejournal.com/userinfo.bml?userid=11742265&t=I
      'liveinternet' => array(
        'name' => 'Live Internet',
        'rewrite' => 'http://www.liveinternet.ru/users/!/',
        ),
      'yaru' => array(
        'name' => 'Я.ру',
        'rewrite' => 'http://!.ya.ru/',
        ),
      );
  }
}
