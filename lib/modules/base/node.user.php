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
      $this->password = md5($this->password);

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

  // РАБОТА С ФОРМАМИ.

  /**
   * Возвращает форму для редактирования профиля.
   *
   * @param bool $simple true, если форма не должна содержать расширенную
   * информацию (историю изменений, список групп итд).
   *
   * @return Form описание формы.
   */
  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    if (!$simple and (null !== ($tab = $this->formGetGroups())))
      $form->addControl($tab);

    $form->title = (null === $this->id)
      ? t('Новый пользователь')
      : t('Пользователь %name', array('%name' => $this->name));

    if ($this->id) {
      $tmp = $form->findControl('node_content_name');

      if ('cms-bugs@molinos.ru' == $this->name) {
        $tmp->description = t('Замените это на свой почтовый адрес или OpenID, если он у вас есть.');
        $form->title = t('Встроенный администратор');
      } elseif (false === strstr($this->name, '@') and false !== strstr($this->name, '.')) {
        if ($tmp)
          $tmp->label = 'OpenID';
        $form->replaceControl('node_content_password', null);
      } else {
        if ($tmp)
          $tmp->label = 'Email';
        $form->replaceControl('node_content_email', null);
      }
    }

    return $form;
  }

  private function formGetGroups()
  {
    $options = array();

    foreach (Node::find(array('class' => 'group', '#sort' => array('name' => 'asc'))) as $g)
      $options[$g->id] = $g->name;

    $tab = new FieldSetControl(array(
      'name' => 'groups',
      'label' => t('Членство в группах'),
      ));
    $tab->addControl(new HiddenControl(array(
      'value' => 'reset_groups',
      'default' => true,
      )));
    $tab->addControl(new SetControl(array(
      'value' => 'node_user_groups',
      'label' => t('Группы, в которых состоит пользователь'),
      'options' => $options,
      )));

    return $tab;
  }

  /**
   * Возвращает данные для формы.
   *
   * @return array данные для формы.  К полученным от родителя данным
   * добавляется список групп, к которым можно прикрепить пользователя.
   */
  public function formGetData()
  {
    $data = parent::formGetData();

    $data['node_user_groups'] = $this->linkListParents('group', true);

    return $data;
  }

  /**
   * Обработка форм.
   *
   * В дополнение к родительским действиям занимается привязкой пользователя к
   * группам.
   *
   * @param array $data полученные от пользователя данные.
   *
   * @return void
   */
  public function formProcess(array $data)
  {
    parent::formProcess($data);

    if (mcms::user()->hasAccess('u', 'group') and !empty($data['reset_groups']))
      $this->linkSetParents(empty($data['node_user_groups']) ? array() : $data['node_user_groups'], 'group');
  }

  /**
   * Возвращает базовую структуру профиля.
   *
   * @see TypeNode::getSchema()
   *
   * return array структура типа документа.  Используется если в БД не найдена.
   */
  public function getDefaultSchema()
  {
    return array(
      'title' => 'Профиль пользователя',
      'adminmodule' => 'admin', // запрещаем выводить в списке контента
      'notags' => true,
      'fields' => array(
        'name' => array(
          'type' => 'EmailControl',
          'label' => 'Email или OpenID',
          'required' => true,
          ),
        'fullname' => array(
          'type' => 'TextLineControl',
          'label' => 'Полное имя',
          'description' => 'Используется в подписях к комментариям, при отправке почтовых сообщений и т.д.',
          ),
        'password' => array(
          'type' => 'PasswordControl',
          'label' => 'Пароль',
          'required' => true,
          ),
        ),
      );
  }
};
