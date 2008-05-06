<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class User
{
  private $node = null;
  private $groups = array();
  private $access = null;
  private $session = null;

  private static $instance = null;

  protected function __construct(UserNode $node = null)
  {
    if (null !== $node) {
      $this->node = $node;
    }

    elseif (null === ($this->session = SessionData::load()) or null === ($uid = $this->session->uid)) {
      $this->node = Node::create('user', array(
        'name' => 'anonymous',
        ));
      foreach (Node::find(array('class' => 'group')) as $tmp)
        if ($tmp->login == 'nobody')
          $this->groups[] = $tmp;
    } else {
      try {
        if (is_string($tmp = mcms::cache($key = 'userprofile:'. $uid)))
          $this->node = unserialize($tmp);
        else
          mcms::cache($key, serialize($this->node = Node::load(array('class' => 'user', 'id' => $uid))));

        if (is_string($tmp = mcms::cache($key = 'usergroups:'. $uid)) and is_array($tmp = unserialize($tmp) and !empty($tmp)))
          $this->groups = $tmp;
        else {
          mcms::cache($key, serialize($this->groups = Node::find(array('class' => 'group', 'published' => 1, 'tagged' => array($uid)))));
        }
      } catch (ObjectNotFoundException $e) {
        $this->node = Node::create('user', array(
          'name' => 'anonymous',
          ));
      }
    }

    if (null !== $this->node and 'anonymous' != $this->node->name)
      mcms::log('auth', t('user=%user, groups=%groups', array(
        '%user' => $this->node->name,
        '%groups' => join(',', array_keys($this->getGroups())),
        )));
  }

  public function hasAccess($mode, $type)
  {
    $map = $this->loadAccess();

    if (array_key_exists($mode, $map) and in_array($type, $map[$mode]))
      return true;

    return false;
  }

  public function checkAccess($mode, $type)
  {
    if (!$this->hasAccess($mode, $type))
      throw new ForbiddenException();
  }

  public function getAccess($mode)
  {
    $map = $this->loadAccess();

    if (array_key_exists($mode, $map))
      return $map[$mode];

    return array();
  }

  // Загружаем информацию о правах.
  private function loadAccess()
  {
    if (null === $this->access) {
      $keys = array_keys($this->getGroups());
      sort($keys);

      if (is_array($result = mcms::cache($ckey = 'access:'. join(',', $keys))))
        return $this->access = $result;

      $result = array();

      if (count($groups = array_keys($this->getGroups()))) {
        $data = mcms::db()->getResults($sql = "SELECT `v`.`name` AS `name`, MAX(`a`.`c`) AS `c`, MAX(`a`.`r`) AS `r`, MAX(`a`.`u`) AS `u`, MAX(`a`.`d`) AS `d` FROM `node` `n` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` INNER JOIN `node__access` `a` ON `a`.`nid` = `n`.`id` WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0 AND `a`.`uid` IN (". join(', ', $groups) .") GROUP BY `v`.`name`");
        $mask = array('c', 'r', 'u', 'd');

        foreach ($data as $row) {
          foreach ($mask as $mode)
            if (!empty($row[$mode]))
              $result[$mode][] = $row['name'];
        }
      }

      mcms::cache($ckey, $this->access = $result);
    }

    return $this->access;
  }

  // ОСНОВНОЙ ИНТЕРФЕЙС

  // Восстановление пользователя из сессии.  Если пользователь не идентифицирован,
  // будет загружен обычный анонимный профиль, без поддержки сессий.
  public static function identify()
  {
    if (array_key_exists('openid_mode', $_GET))
      self::openIDAuthorize($_GET['openid_mode']);

    if (null === self::$instance)
      self::$instance = new User();

    return self::$instance;
  }

  private static function openIDAuthorize($openid_mode)
  {
    self::includeOpenID();

    if ('none' == ($mode = mcms::modconf('auth', 'mode', 'open')))
      throw new RuntimeException(t('Поддержка OpenID отключена администратором.'));

    if ('id_res' == $openid_mode) {
      foreach (array('openid1_claimed_id', 'openid_claimed_id', 'openid_identity') as $key) {
        if (!empty($_GET[$key])) {
          $tmp = bebop_split_url($_GET[$key]);
          break;
        }
      }

      if (empty($tmp))
        throw new RuntimeException('OpenID провайдер не вернул идентификатор.');

      $openid = $tmp['host'];

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
      unset($_SERVER['REQUEST_URI']);

      $sid = md5($openid. microtime() . $_SERVER['HTTP_HOST']);

      // Сохраняем сессию в БД.
      SessionData::db($sid, array('uid' => $node->id));
      setcookie('mcmsid', $sid, time() + 60*60*24*30);
      self::$instance = new User($node);
    } else {
      // Login canceled
      mcms::redirect("/index.php?action=logout");
    }
  }

  // Идентифицирует или разлогинивает пользователя.
  public static function authorize()
  {
    $args = func_get_args();

    if (empty($args)) {
      if (array_key_exists('mcmsid', $_COOKIE)) {
        SessionData::db($_COOKIE['mcmsid'], array());
        setcookie('mcmsid', '');
      }
    }

    elseif (count($args) >= 2) {
      if (strpos($args[0],'@')) { //e-mail в качестве логина
        $node = Node::load(array('class' => 'user', 'name' => $args[0]));

        if ($node->password != md5($args[1]) and empty($args[2]))
          throw new ForbiddenException(t('Введён неверный пароль.'));

        if (!$node->published)
          throw new ForbiddenException(t('Ваш профиль заблокирован.'));

        // Создаём уникальный идентификатор сессии.
        $sid = md5($node->login . $node->password . microtime() . $_SERVER['HTTP_HOST']);

        // Сохраняем сессию в БД.
        SessionData::db($sid, array('uid' => $node->id));
        setcookie('mcmsid', $sid, time() + 60*60*24*30);
        self::$instance = new User($node);
      }

      // Возможно, это не e-mail, а openID.
      else {
        self::includeOpenID();
        self::OpenIDVerify($args[0]);
        exit();
      }
    } else {
      throw new InvalidArgumentException(t('Метод User::authorize() принимает либо два параметра, либо ни одного.'));
    }
  }

  public function __get($key)
  {
    if ('session' === $key) {
      if (null === $this->session)
        throw new ForbiddenException();
      return $this->session;
    }
    return $this->node->$key;
  }

  public function getGroups()
  {
    static $result = null;

    if (null === $result) {
      $result = array();

      foreach ($this->groups as $g)
        $result[$g->id] = $g->login;
    }

    return $result;
  }

  public function hasGroup($name)
  {
    if (bebop_skip_checks())
      return true;

    foreach ($this->groups as $g)
      if ($name == $g->login)
        return true;

    return false;
  }

  public function checkGroup($name)
  {
    if (basename($_SERVER['SCRIPT_NAME']) == 'update.php')
      return;

    if (!$this->hasGroup($name) and !bebop_skip_checks())
      throw new ForbiddenException();
  }

  public static function OpenIDVerify($openid)
  {
    $consumer = self::getConsumer();

    // Begin the OpenID authentication process.
    // No auth request means we can't begin OpenID.

    if (!($auth_request = $consumer->begin($openid)))
      mcms::redirect("/index.php?action=logout");

    $sreg_request = Auth_OpenID_SRegRequest::build(
      array('nickname'), // Required
      array('fullname', 'email') // Optional
      );

    if ($sreg_request)
      $auth_request->addExtension($sreg_request);

    $policy_uris = $_GET['policies'];

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
        displayError("Could not redirect to server: " . $redirect_url->message);
      } else {
        // Send redirect.
        header("Location: ".$redirect_url);
      }
    } else {
      // Generate form markup and render it.
      $form_id = 'openid_message';
      $form_html = $auth_request->formMarkup(self::getTrustRoot(), self::getReturnTo(), false, array('id' => $form_id));

      // Display an error if the form markup couldn't be generated;
      // otherwise, render the HTML.
      if (Auth_OpenID::isFailure($form_html)) {
        displayError("Could not redirect to server: " . $form_html->message);
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

  public static function getStore()
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

  public static  function getConsumer()
  {
    /**
     * Create a consumer object using the store object created
     * earlier.
     */
    $store = self::getStore();
    return new Auth_OpenID_Consumer($store);
  }

  public static function getReturnTo($id = null)
  {
    $url = l('/base.rpc?action=login&id='. urlencode($id), null, null, true);
    // mcms::debug($url, $id);
    return $url;
  }

  public static function getTrustRoot()
  {
    return l('/', null, null, true);
  }

  public static function includeOpenID()
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
