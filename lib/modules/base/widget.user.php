<?php
/**
 * Виджет «профиль пользователя».
 *
 * Используется для вывода и редактирования профиля пользователя.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «профиль пользователя».
 *
 * Используется для вывода и редактирования профиля пользователя.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class UserWidget extends Widget implements iWidget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array описание виджета, ключи: name, description.
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Профиль пользователя',
      'description' => 'Выводит форму авторизации, выхода, регистрации, восстановления пароля и редактирования профиля.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/UserWidget',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * @return Form вкладка с настройками виджета.
   */
  public static function getConfigOptions()
  {
    $groups = array();

    foreach (Node::find(array('class' => 'group')) as $node)
      $groups[$node->id] = $node->name;
    
    return array(
      'submittext' => array(
        'type' => 'TextLineControl',
        'label' => t('Текст для кнопки входа'),
        ),
      'header' => array(
        'type' => 'EnumControl',
        'label' => t('Формат заголовка'),
        'default_label' => t('(без заголовка)'),
        'options' => array(
          'h2' => t('H2'),
          'h3' => t('H3'),
          ),
        ),
      'page' => array(
        'type' => 'EnumControl',
        'label' => t('Отправлять запросы на страницу'),
        'default' => t('Оставаться на текущей'),
        'options' => DomainNode::getFlatSiteMap('select'),
        ),
      );
  }

  /**
   * Препроцессор параметров.
   *
   * @param Context $ctx контекст запроса.
   *
   * @return array параметры виджета.
   */
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['uid'] = $this->get('uid');
    $options['login'] = mcms::user()->login;
    $options['status'] = $this->get('status');
    $options['hash'] = $this->get('hash');

    if ('default' == ($options['action'] = $this->get('action', 'default'))) {
      if (mcms::user()->id)
        $options['action'] = 'logout';
      else
        $options['action'] = 'login';
    }

    $options['#cache'] = false;

    return $options;
  }

  /**
   * Диспетчер GET-запросов.
   *
   * Вызывает один из методов onGet...(), в зависимости от GET-параметра action.
   *
   * @see Widget::dispatch()
   *
   * @param array $options параметры виджета.
   *
   * @return mixed результат работы конкретного обработчика.  При возврате
   * массива с ключём "html" его содержимое обрамляется в div с
   * id=user-widget-имя_виджета и class=user-widget.
   */
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  /**
   * Форма входа.
   */
  protected function onGetLogin(array $options)
  {
    $url = $this->getMeLink('action', 'retry');
    $url->setarg('destination', $this->get('destination'));

    $form = new Form(array(
      'title' => t('Вход'),
      'action' => '?q=base.rpc&action=login&destination=CURRENT',
      ));
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
    $form->addControl(new BoolControl(array(
      'value' => 'remember',
      'label' => t('Помнить 2 недели'),
      )));
    $form->addControl(new SubmitControl(array(
      'text' => (null === $this->submittext) ? t('Войти') : $this->submittext,
      )));
    $output = $form->getXML(Control::data());

    $output .= html::em('link', array(
      'rel' => 'remind',
      'href' => $this->getMeLink('action', 'remind')->string(),
      'text' => t('Восстановить пароль'),
      ));

    $output .= html::em('link', array(
      'rel' => 'register',
      'href' => $this->getMeLink('action', 'register')->string(),
      'text' => t('Зарегистрироваться'),
      ));

    return $output;
  }

  /**
   * Форма выхода.
   */
  protected function onGetLogout(array $options)
  {
    $form = new Form(array(
      'action' => '?q=base.rpc&action=logout&destination=CURRENT',
      ));
    $form->addControl(new SubmitControl(array(
      'text' => t('Выйти'),
      )));
    $output = $form->getXML(Control::data());

    $output .= html::em('link', array(
      'rel' => 'remind',
      'href' => $this->getmelink('action', 'edit')->string(),
      'text' => t('Мои настройки'),
      ));

    return $output;
  }

  /**
   * Форма восстановления пароля.
   */

  /**
   * Возвращает форму регистрации нового пользователя.
   *
   * При вызове от имени авторизованного (не анонимного) пользователя кидает
   * BadRequestException (ошибка 400).
   *
   * @param array $options параметры виджета.
   *
   * @return array данные для шаблона, ключи: mode (всегда «register»), form
   * (HTML код формы или текстовое сообщение об успешной регистрации, с
   * дальнейшими инструкциями).
   */
  protected function onGetRegister(array $options)
  {
    if (mcms::user()->id) {
      $url = new url();
      $url->setarg($this->getInstanceName() .'.action', 'edit');

      $r = new Redirect($url->string());
      $r->send();
    }

    $node = Node::create('user');
    return $node->formGet()->getXML($node);
  }

  /**
   * Форма восстановления пароля.
   */
  protected function onGetRemind(array $options)
  {
    $next = $this->getMeLink('action', 'login');
    $next->setarg($this->name . '.status', 'reminderSent');

    $form = new Form(array(
      'title' => t('Вход'),
      'action' => '?q=base.rpc&action=restore&destination=' . urlencode($next->string()),
      ));
    $form->addControl(new EmailControl(array(
      'value' => 'email',
      'label' => t('Почтовый адрес'),
      'description' => t('Этот адрес вы использовали при регистрации.'),
      'required' => true,
      )));
    $form->addControl(new SubmitControl(array(
      'text' => t('Отправить письмо'),
      )));

    $output = $form->getXML(Control::data());

    $output .= html::em('link', array(
      'rel' => 'login',
      'href' => $this->getMeLink('action', 'login')->string(),
      'text' => t('Не надо восстанавливать, я помню пароль.'),
      ));

    return $output;
  }

  /**
   * Форма редактирования профиля.
   */
  protected function onGetEdit(array $options)
  {
    if (!mcms::user()->id)
      $this->ctx->redirect($this->getMeLink('action', 'register')->string());

    $node = mcms::user()->getNode();
    return $node->formGet()->getXML($node);
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

  /**
   * Обработчик форм.
   *
   * @todo устранить в пользу nodeapi.rpc и base.rpc.
   *
   * @param array $options параметры виджета.
   *
   * @param array $post данные формы.
   *
   * @param array $files загруженные файлы, если есть.
   *
   * return string адрес для перенаправления пользователя.
   */
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

    if (empty($_GET['destination']))
      $dest = bebop_split_url();
    else
      $dest = bebop_split_url($_GET['destination']);

    if ($status !== null)
      $dest['args'][$this->me->name]['status'] = $status;

    $dest['args']['widget'] = null;

    return bebop_combine_url($dest, false);
  }

  /**
   * Возвращает указанную форму.
   *
   * @param string $id идентификатор формы (user-logout-form,
   * profile-edit-form, profile-remind-form).
   *
   * @see Widget::formRender()
   *
   * @return Form описание формы.
   */
  public function formGet($id)
  {
    $user = mcms::user();

    switch ($id) {
    case 'user-logout-form':
      $form = new Form(array(
        'action' => '?q=base.rpc&action=logout',
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

  /**
   * Возвращает данные для формы.
   *
   * @see Widget::formRender()
   *
   * @param string $id идентификатор формы.
   *
   * @return array данные для формы.
   */
  public function formGetData($id)
  {
    switch ($id) {
    case 'user-logout-form':
      return array(
        'action' => 'logout',
        );

    case 'profile-remind-form':
      return array();

    case 'profile-edit-form':
      $uid = empty($_GET['profile_uid']) ? mcms::user()->id : $_GET['profile_uid'];
      $user = Node::load(array('class' => 'user', 'id' => $uid));
      return $user;

    case 'user-register-form':
      return array();

    default:
      mcms::debug("Form {$id} is not handled by UserWidget");
    }
  }

  private function getMeLink($arg, $value)
  {
    $url = new url();
    $url->setarg($this->name . '.' . $arg, $value);
    return $url;
  }
};
