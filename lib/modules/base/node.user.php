<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class UserNode extends Node implements iContentType
{
  // Сохраняем старый пароль, на случай сброса.
  private $origpassword = null;

  protected function __construct(array $data)
  {
    $this->origpassword = empty($data['password']) ? null : $data['password'];
    parent::__construct($data);
  }

  // Шифруем пароль.
  public function save($clear = true)
  {
    if (empty($this->login))
      $this->login = $this->name;

    // Возвращаем старый пароль, если не изменился.
    if (empty($this->password))
      $this->password = $this->origpassword;

    // Шифруем новый пароль.
    elseif ($this->password != $this->origpassword)
      $this->password = md5($this->password);

    if ($this->id === null) {
      if (Node::count(array('class' => 'user', 'name' => $this->name)))
        throw new UserErrorException("Имя занято", 400, "Имя занято", "Пользователь с логином &laquo;{$this->login}&raquo; уже существует.");

      if (!empty($this->email) and Node::count(array('class' => 'user', 'email' => $this->email)))
        throw new UserErrorException("Адрес занят", 400, "Адрес занят", "Пользователь с почтовым адресом &laquo;{$this->email}&raquo; уже существует.&nbsp; Забыли пароль?&nbsp; Воспользуйтесь функцией восстановления.");
    }

    parent::save($clear);
  }

  // Сохранение фиксированных прав.
  public function setAccess(array $perms, $reset = true)
  {
    parent::setAccess(array(
      'User Managers' => array('r', 'u', 'd'),
      'Visitors' => array('r'),
      ), true);
  }

  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id) {
      $data['Visitors']['r'] = 1;
      $data['User Managers']['r'] = 1;
      $data['User Managers']['u'] = 1;
      $data['User Managers']['d'] = 1;
    }

    return $data;
  }

  public function duplicate()
  {
    $this->login = preg_replace('/_[0-9]+$/', '', $this->login) .'_'. rand();
    $this->email = null;

    parent::duplicate();
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    if (!$simple and (null !== ($tab = $this->formGetGroups())))
      $form->addControl($tab);

    $form->title = (null === $this->id)
      ? t('Новый пользователь')
      : t('Профиль пользователя %login', array('%login' => $this->login));

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

  public function formGetData()
  {
    $data = parent::formGetData();

    $data['node_user_groups'] = $this->linkListParents('group', true);

    return $data;
  }

  public function formProcess(array $data)
  {
    parent::formProcess($data);

    if (mcms::user()->hasAccess('u', 'group') and !empty($data['reset_groups']))
      $this->linkSetParents(empty($data['node_user_groups']) ? array() : $data['node_user_groups'], 'group');
  }

  public function delete()
  {
    return parent::delete();
  }
};
