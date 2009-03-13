<?php
/**
 * Тип документа «user» — профиль пользователя.
 *
 * Используется для формирования структуры данных сайта (не путать со структурой
 * страниц, которая описывается с помощью DomainNode).
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Тип документа «user» — профиль пользователя.
 *
 * Используется для формирования структуры данных сайта (не путать со структурой
 * страниц, которая описывается с помощью DomainNode).
 *
 * @package mod_base
 * @subpackage Types
 */
class UserNode extends Node implements iContentType
{
  /**
   * Сохранение профиля.
   *
   * Шифрует пароль при его изменении (MD5), проверяет имя на уникальность.
   *
   * @return Node ссылка на себя (для построения цепочек).
   */
  public function save()
  {
    parent::checkUnique('name', t('Пользователь с именем %name уже есть.',
      array('%name' => $this->name)));

    return parent::save();
  }

  /**
   * Клонирование профиля.
   *
   * @param integer $parent новый код родителя, не используется и не работает.
   * Нужен только для подавления нотиса о несоответствии декларации
   * родительской.
   *
   * @return Node ссылка на новый объект.
   */
  public function duplicate($parent = null)
  {
    $this->name = $this->name . '/tmp' . rand();
    return parent::duplicate($parent);
  }

  /**
   * Проверка пароля.
   *
   * @param string $password пробуемый пароль.
   *
   * @return bool true, если пароль верен, в противном случае false.
   */
  public function checkpw($password)
  {
    if (empty($this->password) and empty($password))
      return true;
    if ($this->password == md5($password))
      return true;
    return false;
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();
    $user = Context::last()->user;

    if ($user->id != $this->id and $this->published and $user->hasAccess('u', 'user'))
      $links['sudo'] = array(
        'href' => '?q=user.rpc&action=su&uid='. $this->id
          .'&destination=CURRENT',
        'title' => 'Переключиться в пользователя',
        'icon' => 'sudo',
        );

    $links['search'] = array(
      'href' => '?q=admin.rpc&action=list&cgroup=content&search=uid%3A'. $this->id,
      'title' => t('Найти документы пользователя'),
      'icon' => 'search',
      );

    return $links;
  }

  public function getName()
  {
    if ($name = $this->fullname)
      return $name;
    if (0 === strpos($this->name, 'http://'))
      return rtrim(substr($this->name, 7), '/');
    return $this->name;
  }

  public function getEmail()
  {
    if (false !== strpos($this->name, '@'))
      return $this->name;

    if (false !== strpos($this->email, '@'))
      return $this->email;

    throw new RuntimeException(t('У объекта отсутствует поле email.'));
  }

  public static function getDefaultSchema()
  {
    return array(
      'name' => array(
        'type' => 'EmailControl',
        'label' => t('Email или OpenID'),
        'required' => true,
        ),
      'fullname' => array(
        'type' => 'TextLineControl',
        'label' => t('Полное имя'),
        'description' => 'Используется в подписях к комментариям, при отправке почтовых сообщений и т.д.',
        ),
      'password' => array(
        'type' => 'PasswordControl',
        'label' => t('Пароль'),
        ),
      'groups' => array(
        'type' => 'SetControl',
        'label' => t('Состоит в группах'),
        'group' => t('Доступ'),
        'dictionary' => 'group',
        'volatile' => true,
        'parents' => true,
        ),
      );
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Профиль пользователя «%name»', array('%name' => $this->getName()))
      : t('Добавление нового пользователя');
  }

  /**
   * Возвращает адрес обработчика формы.
   *
   * Если пользователь создаётся анонимно — это регистрация,
   * обрабатываем её с помощью user.rpc.
   */
  public function getFormAction()
  {
    if (!$this->id and !Context::last()->user->id) {
      $next = Context::last()->get('destination', '');
      return '?q=user.rpc&action=register&destination=' . urlencode($next);
    }

    return parent::getFormAction();
  }

  public function getFormFields()
  {
    $schema = parent::getFormFields();

    if (!Context::last()->user->hasAccess('u', 'group') and isset($schema['groups']))
      unset($schema['groups']);

    return $schema;
  }

  /**
   * Используется для выполнения всяких пост-регистрационных процедур,
   * лучшего места пока найти не удалось. Вызывается при сохранении
   * пользователя вручную или при авторизации через OpenID.
   */
  public function setRegistered(Context $ctx)
  {
    if (is_array($list = $ctx->modconf('auth', 'groups', array()))) {
      $params = array();
      $this->onSave("INSERT INTO `node__rel` (`tid`, `nid`) SELECT `id`, %ID% FROM `node` WHERE `class` = 'group' AND `id` " . sql::in($list, $params));
    }
  }

  /**
   * Обработка формы, шифрует пароль.
   */
  public function formProcess(array $data)
  {
    if (!$this->id)
      $this->setRegistered();

    $oldpassword = $this->password;
    $res = parent::formProcess($data);
    $this->password = empty($this->password)
      ? $oldpassword
      : md5($this->password);
    return $res;
  }

  public function setPassword($password)
  {
    $this->password = md5($password);
  }

  /**
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => 'user',
        'deleted' => 0,
        ), $ctx->db);
    } catch (ObjectNotFoundException $e) {
      // TODO: install
    }
  }
};
