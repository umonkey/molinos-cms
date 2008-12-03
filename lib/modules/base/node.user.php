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
   * При загрузке сюда сохраняется старый пароль, чтобы знать, когда его нужно
   * сохранить (т.к. при редактировании, если пользователь не ввёл пароль, его
   * не надо сбрасывать, а надо оставить неизменным).
   */
  private $origpassword = null;

  /**
   * Расширенный конструктор.
   *
   * Сохранянет пароль в свойстве origpassword.
   *
   * @param array $data содержимое ноды, передаётся базовому конструктору.
   */
  protected function __construct(array $data)
  {
    $this->origpassword = empty($data['password']) ? null : $data['password'];
    return parent::__construct($data);
  }

  /**
   * Сохранение профиля.
   *
   * Шифрует пароль при его изменении (MD5), проверяет имя на уникальность.
   *
   * @return Node ссылка на себя (для построения цепочек).
   */
  public function save()
  {
    $isnew = empty($this->id);

    // Возвращаем старый пароль, если не изменился.
    if (empty($this->password))
      $this->password = $this->origpassword;

    // Шифруем новый пароль.
    elseif ($this->password != $this->origpassword)
      $this->origpassword = $this->password = md5($this->password);

    parent::checkUnique('name', t('Пользователь с именем %name уже есть.',
      array('%name' => $this->name)));

    parent::save();

    if ($isnew and is_array($authconf = mcms::modconf('auth'))) {
      if (!empty($authconf['groups'])) {
        $this->linkSetParents($authconf['groups'], 'group');
      }
    }

    return $this;
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
    $this->login = preg_replace('/_[0-9]+$/', '', $this->login) .'_'. rand();
    $this->email = null;

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

    if (mcms::user()->id != $this->id and $this->published and mcms::user()->hasAccess('u', 'user'))
      $links['sudo'] = array(
        'href' => '?q=base.rpc&action=su&uid='. $this->id
          .'&destination=CURRENT',
        'title' => 'Переключиться в пользователя',
        'icon' => 'sudo',
        );

    $links['search'] = array(
      'href' => '?q=admin/content/list&search=uid%3A'. $this->id,
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

  public function getDefaultSchema()
  {
    $result = array(
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

    if (!mcms::user()->hasAccess('u', 'user'))
      unset($result['groups']);

    return $result;
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
   * обрабатываем её с помощью base.rpc.
   */
  public function getFormAction()
  {
    if (!$this->id and !mcms::user()->id) {
      $next = Context::last()->get('destination', '');
      return '?q=base.rpc&action=register&destination=' . urlencode($next);
    }

    return parent::getFormAction();
  }
};
