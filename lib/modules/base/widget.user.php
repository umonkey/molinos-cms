<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UserWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Профиль пользователя',
      'description' => 'Возвращает информацию о текущем пользователе, позволяет входить в систему, регистрироваться и восстанавливать забытые пароли.',
      );
  }

  public static function formGetConfig()
  {
    $groups = array();

    foreach (Node::find(array('class' => 'group')) as $node)
      $groups[$node->id] = $node->name;
    
    $form = parent::formGetConfig();

    $form->addControl(new SetControl(array(
      'value' => 'config_groups',
      'label' => t('Группы для новых пользователей'),
      'options' => $groups,
      'description' => t('Укажите список групп, в которые будут добавлены пользователи, регистрирующиеся на сайте.'),
      )));

    $form->addControl(new TextLineControl(array(
      'value' => 'config_submittext',
      'label' => t('Текст для кнопки входа'),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_header',
      'label' => t('Формат заголовка'),
      'default' => t('Без заголовка'),
      'options' => array(
        'h2' => t('H2'),
        'h3' => t('H3'),
        ),
      )));

    $form->addControl(new EnumControl(array(
      'value' => 'config_page',
      'label' => t('Отправлять запросы на страницу'),
      'default' => t('Оставаться на текущей'),
      'options' => DomainNode::getFlatSiteMap('select'),
      )));

    return $form;
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['uid'] = $ctx->get('uid');
    $options['login'] = $this->user->login;
    $options['root'] = join('/', $ctx->ppath) .'/';
    $options['action'] = $ctx->get('action', 'default');
    $options['status'] = $ctx->get('status');
    $options['hash'] = $ctx->get('hash');

    if (empty($options['uid']))
      $options['#nocache'] = true;

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    $result = $this->dispatch(array($options['action']), $options);

    if (is_array($result) and array_key_exists('html', $result))
      $result['html'] = "<div id='user-widget-{$this->me->name}' class='user-widget'>". $result['html'] ."</div>";

    return $result;
  }

  protected function onGetDefault(array $options)
  {
    $user = mcms::user();

    $result = array(
      'uid' => $user->id,
      'name' => $options['login'],
      'groups' => $user->getGroups(true),
      'form' => null,
      );

    // Добавка для вошедших.
    if (!empty($result['uid'])) {
      $result['mode'] = 'logout';
      $result['form'] = $this->getLogoutForm($options);
    }

    // Добавка для невошедших.
    else {
      $result['mode'] = 'login';
      $result['form'] = $this->getLoginForm($options);
    }

    return $result;
  }

  protected function onGetRegister(array $options)
  {
    if ($this->user->id)
      throw new UserErrorException("Вы уже авторизованы", 400, "Вы уже авторизованы", "Регистрация возможна только для анонимных пользователей.");

    switch ($options['status']) {
    case 'registered':
      $output = "<p>Регистрация прошла успешно.&nbsp; В ближайшее время на указанный в анкете почтовый адрес придёт инструкция по активации вашей новой учётной записи.</p>";
      break;

    default:
      $output = parent::formRender('user-register-form');
      break;
    }

    return array(
      'mode' => 'register',
      'form' => $output,
      );
  }

  protected function onGetConfirm(array $options)
  {
    $pdo = mcms::db();

    // Найдём неутверждённого пользователя с совпадающим хэшем.
    $uid = $pdo->getResult("SELECT `id` FROM `node_user` WHERE MD5(CONCAT(`id`, ':', `email`)) = :hash", array(':hash' => $options['hash']));

    // Загрузим профиль.  Если его нет -- нарвёмся на исключение.
    $node = Node::load($uid);

    // Загрузим группу Visitors -- она определяет активацию.
    $visitors = Node::load(array('class' => 'group', 'login' => 'Visitors'));

    // Проверим, не активирован ли уже пользователь.
    if (in_array($visitors->id, $node->linkListParents('group', true)))
      throw new UserErrorException("Пользователь уже активен", 400, "Пользователь уже активен", "Эта учётная запись уже была активирована.");

    // Включаем.
    $node->linkAddParent($visitors->id);

    mcms::flush();

    // Сообщаем администратору.
    if (null !== ($to = mcms::config('modules_user_notifications'))) {
      BebopMimeMail::send(null, $to, "Новый пользователь на сайте {$_SERVER['HTTP_HOST']}",
        "<p>На сайте {$_SERVER['HTTP_HOST']} только что успешно завершил регистрацию новый пользователь: {$node->name}.</p>");
    }

    // Идентифицируем пользователя.
    mcms::auth($node->login, null, true);

    // Перекидываем на текущую страницу, но без восстановления.
    $url = bebop_split_url();
    $url['args'][$this->me->name] = array(
      'status' => 'activated',
      );
    exit(mcms::redirect(bebop_combine_url($url, false)));
  }

  protected function onGetRestore(array $options)
  {
    if ($options['status'] == 'sent') {
      $form = '<h2>'. t('Восстановление пароля') .'</h2>';
      $form .= '<p>'. t('Инструкция по восстановлению пароля была отправлена на электронную почту.') .'</p>';
      $form .= '<p><a href=\'/\'>'. t('Вернуться на главную страницу') .'</a></p>';
    } else {
      $form = parent::formRender('profile-remind-form');
    }

    return array(
      'mode' => 'restore',
      'form' => $form,
      );
  }

  protected function onGetEdit(array $options)
  {
    switch ($options['status']) {
    case 'ok':
      $output = "<p>Настройки профиля сохранены.</p>";
      break;

    default:
      $output = parent::formRender('profile-edit-form');
      break;
    }

    return array('form' => $output);
  }

  protected function onGetLogout(array $options)
  {
    mcms::auth();

    if (!empty($_GET['destination']))
      $url = $_GET['destination'];
    else {
      $url = bebop_split_url();
      $url['args'][$this->getInstanceName()] = null;
    }

    bebop_on_json(array(
      'status' => 'ok',
      ));

    mcms::redirect($url);
  }

  private function getLoginForm(array $options)
  {
    $url = new url();
    $url->setarg($this->me->name .'.action', 'register');

    $output = parent::formRender('user-login-form');
    $output .= t("<p class='profileRegisterLink'><a href='@url'>"
      ."Зарегистрироваться</a></p>", array(
        '@url' => strval($url),
        ));

    return $output;
  }

  private function getLogoutForm(array $options)
  {
    $user = $this->user;

    $output = parent::formRender('user-logout-form');
    /*
    $output .= "<p class='profileEditLink'>". t('<a href=\'@link\'>Изменить профиль</a>', array(
      '@link' => mcms_url(array('args' => array(
        'destination' => 'CURRENT',
        $this->getInstanceName() => array('action' => 'edit'),
        ))),
      )) ."</p>";
    */

    return $output;
  }

  public function onPost(array $options, array $post, array $files)
  {
    $status = null;

    if (empty($post))
      return;

    if ($options['action'] == 'default' and !empty($post['action']))
      $options['action'] = $post['action'];

    switch ($options['action']) {
    case 'edit':
      $user = mcms::user();

      if ($options['uid'] != $user->id and !$user->hasAccess('u', 'user'))
        throw new PageNotFoundException(); // FIXME: 403

      $node = Node::load($options['uid']);

      foreach ($post['node'] as $k => $v)
        $node->$k = $v;

      $node->save();
      $node->publish($node->rid);

      mcms::flush();

      $status = 'ok';
      break;

    case 'register':
      $node = Node::create('user');
      $node->formProcess($post, $files);

      BebopMimeMail::send(null, $node->email, "Регистрация на сайте {$_SERVER['HTTP_HOST']}", $body = 
        t("<p>На сайте %host была произведена попытка регистрации с указанием этого почтового адреса.&nbsp; "
        ."Если вы действительно хотите зарегистрироваться, вам необходимо <a href='{$confirm}'>активировать учётную запись</a>, "
        ."в противном случае она будет автоматически удалена в течение недели.</p>", array(
          '%host' => $_SERVER['HTTP_HOST'],
          '%confirm' => l(null, array('widget' => null, $this->getInstanceName() => array(
            'action' => 'confirm',
            'confirm' => md5($node->id .':'. $node->email),
            ))),
          )));

      $status = 'registered';
      break;

    default:
      mcms::debug($options, $post);
      throw new PageNotFoundException();
    }

    bebop_on_json(array(
      'status' => $status,
      ));

    if (empty($_GET['destination']))
      $dest = bebop_split_url();
    else
      $dest = bebop_split_url($_GET['destination']);

    if ($status !== null)
      $dest['args'][$this->me->name]['status'] = $status;

    $dest['args']['widget'] = null;

    return bebop_combine_url($dest, false);
  }

  // С этим виджетом всегда разрешено работать.
  public function checkRequiredGroups()
  {
    return true;
  }

  public function formGet($id)
  {
    $user = mcms::user();

    switch ($id) {
    case 'user-logout-form':
      $form = new Form(array(
        'action' => 'base.rpc?action=logout',
        ));

      if (null !== $this->header) {
        $form->title = $this->header ? t('Профиль') : null;
        $form->header = $this->header;
      }

      if ($user->id)
        $form->addControl(new InfoControl(array(
          'text' => t('Вы идентифицированы как %login', array('%login' => $user->login)),
          )));

      $form->addControl(new HiddenControl(array(
        'value' => 'action',
        )));

      $form->addControl(new SubmitControl(array(
        'text' => t('Выйти'),
        )));

      return $form;

    case 'user-login-form':
      $tmp = bebop_split_url();
      $tmp['args'][$this->getInstanceName()]['action'] = 'retry';
      $tmp['args']['destination'] = $this->ctx->get('destination');

      $form = new Form(array(
        'title' => t('Вход'),
        'action' => 'base.rpc?action=login&destination='. urlencode(bebop_combine_url($tmp, false)),
        ));

      if (null !== $this->header) {
        $form->title = $this->header ? t('Идентификация') : null;
        $form->header = $this->header;
      }

      if ('wrong' == $this->options['status']) {
        $next = empty($_GET['destination']) ? $_SERVER['REQUEST_URI'] : $_GET['destination'];

        $link = t("Вы ввели неверные данные.&nbsp; <a href='@remind' class='remind'>Забыли пароль?</a>", array(
          '@remind' => mcms_url(array('args' => array(
            'destination' => $next,
            $this->getInstanceName() => array('action' => 'restore'),
            ))),
          ));

        $form->addControl(new InfoControl(array(
          'text' => $link,
          )));
      }

      $form->addControl(new HiddenControl(array(
        'value' => 'action',
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'login',
        'label' => t('Имя'),
        'required' => true,
        )));
      $form->addControl(new PasswordControl(array(
        'value' => 'password',
        'label' => t('Пароль'),
        'required' => true,
        )));
      $form->addControl(new SubmitControl(array(
        'text' => (null === $this->submittext) ? t('Войти') : $this->submittext,
        )));

      return $form;

    case 'profile-edit-form':
      $uid = empty($this->options['uid']) ? $user->id : $this->options['uid'];

      if (empty($uid))
        throw new ForbiddenException(t('Только зарегистрированные пользователи могут редактировать профиль.'));

      $profile = Node::load(array('class' => 'user', 'id' => $uid));
      $form = $profile->formGet();

      if (!empty($this->options['hash'])) {
        if (mcms::user()->id)
          throw new PageNotFoundException();

        if ($this->options['hash'] != md5($profile->password))
          throw new PageNotFoundException();

        $form->intro = t('Вы вошли в систему используя одноразовый пароль.&nbsp; Вы теперь можете изменить настройки профиля, в том числе &mdash; ввести новый пароль.');
      }

      if (null !== $this->header) {
        $form->title = $this->header ? t('Редактирование профиля') : null;
        $form->header = $this->header;
      }

      return $form;

    case 'profile-remind-form':
      $form = new Form(array(
        'title' => t('Восстановление пароля'),
        ));

      if (!empty($this->options['status']) and $this->options['status'] == 'notfound')
        $text = t('Указанный почтовый адрес или пользователь не зарегистрирован.');
      else
        $text = t('Инструкция по восстановлению пароля будет отправлена на введённый почтовый адрес, если он нам известен, или на почтовый адрес пользователя, имя которого вы введёте.');

      $form->addControl(new InfoControl(array('text' => $text)));

      if (null !== $this->header) {
        $form->title = $this->header ? t('Восстановление пароля') : null;
        $form->header = $this->header;
      }

      $form->addControl(new TextLineControl(array(
        'value' => 'identifier',
        'label' => t('Логин или почтовый адрес'),
        'required' => true,
        )));
      $form->addControl(new SubmitControl(array(
        'text' => t('Напомнить'),
        )));
      return $form;

    case 'user-register-form':
      $user = Node::create('user');
      return $user->formGet();
    }
  }

  public function formGetData($id)
  {
    switch ($id) {
    case 'user-logout-form':
      return array(
        'action' => 'logout',
        );

    case 'user-login-form':
      return array(
        'action' => 'login',
        'destination' => empty($_GET['destination']) ? null : $_GET['destination'],
        );

    case 'profile-remind-form':
      return array();

    case 'profile-edit-form':
      $uid = empty($_GET['profile_uid']) ? mcms::user()->id : $_GET['profile_uid'];
      $user = Node::load(array('class' => 'user', 'id' => $uid));
      return $user->formGetData();

    case 'user-register-form':
      return array();

    default:
      mcms::debug("Form {$id} is not handled by UserWidget");
    }
  }

  public function formProcess($id, array $data)
  {
    if (!empty($data['destination']))
      $next = $data['destination'];
    elseif (!empty($_GET['destination']))
      $next = $_GET['destination'];
    else
      $next = $_SERVER['REQUEST_URI'];

    $res = array(
      'status' => 'ok',
      'message' => null,
      );

    switch ($id) {
    case 'user-logout-form':
      mcms::auth();

      $res['message'] = t("Сеанс работы с системой завершён, теперь вы можете <a href='@link'>продолжить работу с сайтом</a> анонимно.", array('@link' => $_SERVER['REQUEST_URI']));
      break;

    case 'user-login-form':
      try {
        mcms::auth($data['login'], $data['password']);
      } catch (ForbiddenException $e) {
        $res['status'] = 'wrong';
      }

      break;

    case 'profile-remind-form':
      $data = mcms::db()->getResult("SELECT `n`.`id` as `id`, `u`.`rid` as `rid`, `u`.`email` as `email` FROM `node_user` `u` INNER JOIN `node` `n` ON `n`.`rid` = `u`.`rid` WHERE `u`.`email` = :s1 OR `u`.`login` = :s2 LIMIT 1", array(':s1' => $data['identifier'], ':s2' => $data['identifier']));

      if (empty($data)) {
        $res['status'] = 'notfound';
        $res['message'] = t('Указанное имя пользователя или почтовый адрес не зарегистрированы.');
      } else {
        $subject = t("Восстановление пароля на сайте %host", array('%host' => $_SERVER['HTTP_HOST']));

        $body = t("<p>Поступил запрос на восстановление пароля для пользователя %user на сайте %host.&nbsp; "
          ."Если вы действительно хотите изменить пароль, воспользуйтесь <a href='http://%host%resetlink'>этой ссылкой</a>.&nbsp; "
          ."Просто проигнорируйте это сообщение, если вы не хотите менять пароль.</p>",
          array(
            '%user' => $data['login'],
            '%host' => $_SERVER['HTTP_HOST'],
            '%resetlink' => l(null, array('widget' => null, $this->me->name => array(
              'action' => 'edit',
              'uid' => $data['id'],
              'hash' => md5($data['password']),
              ))),
            ));

        BebopMimeMail::send(null, $data['email'], $subject, $body);

        $res['status'] = 'sent';
        $res['message'] = t('Данные для доступа к вашей учетной записи были отправлены на e-mail, указанный в вашем профиле.');
      }
      break;

    case 'profile-edit-form':
      if (null === ($uid = $this->options['uid']))
        $uid = mcms::user()->id;

      $user = Node::load(array('class' => 'user', 'id' => $uid));

      if (!empty($this->options['uid'])) {
        if (mcms::user()->id == 0 and !empty($this->options['uid']) and (md5($user->password) != $this->options['hash']))
          throw new PageNotFoundException();

        mcms::auth($data['node_content_login'], $data['node_content_password'], true);
      }

      $user->formProcess($data);
      break;

    case 'user-register-form':
      $user = Node::create('user');
      return $user->formProcess($data);

    default:
      mcms::debug($id, $data);
    }

    bebop_on_json($res);

    if (null !== $res['status'] and 'ok' != $res['status']) {
      $url = bebop_split_url();

      $meargs = $res;
      $meargs['message'] = null;

      $url['args'][$this->getInstanceName()] = $meargs;
      $url['args']['destination'] = $next;

      $next = bebop_combine_url($url, false);
    }

    mcms::redirect($next);
  }

  // Формирует урл для нужного действия.
  private function getActionUrl($action = null)
  {
    $path = '';

    if (!empty($this->page)) {
      $base = mcms::config('basedomain');

      $nodes = Node::load(array('class' => 'domain', 'id' => $this->page))->getParents();

      foreach ($nodes as $idx => $em) {
        if (empty($em['parent_id']))
          $path .= 'http://'. str_replace('DOMAIN', $base, $em['name']) .'/';
        else
          $path .= $em['name'] .'/';
      }
    }

    $url = bebop_split_url($path);
    $url['args'][$this->getInstanceName()]['action'] = $action;
    $url['args']['destination'] = empty($_GET['destination']) ? $_SERVER['REQUEST_URI'] : $_GET['destination'];

    return bebop_combine_url($url, false);
  }
};
