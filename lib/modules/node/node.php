<?php

require_once(dirname(__FILE__) .'/node.inc');
require_once(dirname(__FILE__) .'/node-access-control.inc');
require_once(dirname(__FILE__) .'/node-query-builder.inc');

class ObjectNotFoundException extends UserErrorException
{
  public function __construct()
  {
    parent::__construct("Объект не найден", 404, "Объект не найден", "Вы попытались обратиться к объекту, который не удалось найти.");
  }
};

class ValidationError extends UserErrorException
{
  public function __construct($name, $message = null)
  {
    if ($message === null)
      $message = "Вы не заполнили поле &laquo;{$name}&raquo;, которое нужно заполнить обязательно.&nbsp; Пожалуйста, вернитесь назад и проверьте введённые данные.";

    parent::__construct("Ошибка ввода данных", 400, "Ошибка в поле <span class='highlight'>&laquo;{$name}&raquo;</span>", $message);
  }
};

// Интерфейс для работы с типами документов.
interface iContentType
{
};

class Node extends NodeBase implements iContentType, iModuleConfig
{
  // Создаём пустой объект указанного типа, проверяем тип на валидность.
  protected function __construct(array $data = null)
  {
    $this->data = $data;
  }

  // Форматирует документ в соответствии с шаблоном.
  public function render()
  {
    return bebop_render_object("class", $this->class, "all", $this->data);
  }

  // Проверка прав на объект.  Менеджеры контента всегда всё могут.
  public function checkPermission($perm)
  {
    if (mcms::user()->hasGroup('Content Managers'))
      return true;
    return NodeBase::checkPermission($perm);
  }

  // РАБОТА С ФОРМАМИ.

  // Дополняет стандартную форму редактирования объекта.
  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);
    $user = mcms::user();

    // Добавляем вкладку с правами.
    if (!$simple and $user->hasGroup('Access Managers') and substr($_SERVER['REQUEST_URI'], 0, 7) == '/admin/') {
      $options = array();

      foreach ($this->getAccess() as $k => $v)
        $options[$k] = $v['name'];

      $tab = new FieldSetControl(array(
        'name' => 'access',
        'label' => t('Доступ'),
        ));
      $tab->addControl(new AccessControl(array(
        'value' => 'node_access',
        'options' => $options,
        )));
      $form->addControl($tab);
    }

    return $form;
  }

  public function formGetData()
  {
    $user = mcms::user();

    $data = parent::formGetData();

    if ($user->hasGroup('Access Managers'))
      $data['node_access'] = $this->getAccess();

    return $data;
  }

  public function formProcess(array $data)
  {
    if (null === $this->id and empty($data['node_access']))
      // Документы без прав создаются, как правило,
      // через сайт, в виде обратной связи.
      $data['node_access'] = array(
        'Content Managers' => array('r', 'u', 'd'),
        'Visitors' => array('r'),
        );

    parent::formProcess($data);

    $user = mcms::user();

    if ($user->hasGroup('Access Managers')) {
      $this->setAccess(empty($data['node_access']) ? array() : $data['node_access']);
    } else {
    }
  }

  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id and get_class($this) == 'Node') {
      $data['Content Managers']['r'] = 1;
      $data['Content Managers']['u'] = 1;
      $data['Content Managers']['d'] = 1;
      $data['Visitors']['r'] = 1;
    }

    return $data;
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new NumberControl(array(
      'value' => 'config_archive_limit',
      'label' => t('Количество архивных ревизий'),
      'default' => 10,
      'description' => t('При сохранении документов будет оставлено указанное количество архивных ревизий, все остальные будут удалены.'),
      )));

    return $form;
  }
};

// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:
