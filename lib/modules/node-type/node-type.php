<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(dirname(__FILE__) .'/node-type-control.inc');

class TypeNode extends Node implements iContentType, iScheduler, iModuleConfig
{
  public function __construct(array $data)
  {
    if (empty($data['fields']))
      $data['fields'] = array(
        'name' => array(
          'label' => t('Заголовок'),
          'description' => t('Отображается в списках документов как в административном интерфейсе, так и на самом сайте.'),
          'type' => 'TextLineControl',
          'required' => true,
          ),
        );

    parent::__construct($data);
  }

  public function save($clear = true, $forcedrev = null)
  {
    mcms::user()->checkGroup('Schema Managers');

    if (empty($this->title))
      $this->title = $this->name;

    if (null === $this->id) {
      try {
        $node = Node::load(array('class' => 'type', 'name' => $this->name));
        throw new ForbiddenException(t("Тип документа со внутренним именем \"%name\" уже есть, повторное использование имени не допускается.", array('%name' => $this->name)));
      } catch (ObjectNotFoundException $e) {
        $this->data['published'] = true;
      }
    }

    // Всегда сохраняем без очистки.
    parent::save(false, $forcedrev);

    mcms::cache('schema', null);
  }

  public function duplicate()
  {
    $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();
    parent::duplicate();
  }

  // Возвращает информацию о типе документа, включая поля.
  public function getFields()
  {
    return empty($this->fields) ? array() : $this->fields;
  }

  // Возвращает описание отдельного поля.
  public function fieldGet($name)
  {
    if (!empty($this->data['fields'][$name]) and is_array($this->data['fields'][$name]))
      return $this->data['fields'][$name];
    return null;
  }

  // Изменяет (или добавляет) поле.
  public function fieldSet($name, array $data)
  {
    $this->data['fields'][$name] = $data;
  }

  // Удаляет поле.
  public function fieldDelete($name)
  {
    if (empty($this->data['fields'][$name]))
      return false;
    unset($this->data['fields'][$name]);
    return true;
  }

  // Возвращает true, если указанный тип может содержать файлы.
  public static function checkHasFiles($class)
  {
    $result = mcms::cache('types_with_files');

    if (!is_array($result)) {
      $result = array();

      foreach (self::getSchema() as $name => $info) {
        if (!empty($info['hasfiles']))
          $result[] = $name;
        else if (array_key_exists('fields', $info) and is_array($info['fields'])) {
          foreach ($info['fields'] as $field) {
            if (mcms_ctlname($field['type']) == 'AttachmentControl') {
              $result[] = $name;
              break;
            }
          }
        }
      }

      mcms::cache('types_with_files', $result);
    }

    return in_array($class, $result);
  }

  // Возвращает актуальное описание схемы -- все классы, все поля.
  public static function getSchema($name = null, $reload = false)
  {
    if ($reload or !is_array($result = mcms::cache('schema')) or empty($result)) {
      $result = array();

      foreach (Tagger::getInstance()->getChildrenData("SELECT `n`.`id`, `n`.`rid`, `r`.`name`, `r`.`data`, `t`.* FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` LEFT JOIN `node_type` `t` ON `t`.`rid` = `r`.`rid` WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0", false, false, false) as $type)
        $result[$type['name']] = $type;

      mcms::cache('schema', $result);
    }

    if ($name !== null) {
      $result = empty($result[$name])
        ? array('fields' => array())
        : $result[$name];
    }

    return $result;
  }

  public function fieldMove($name, $delta = 0)
  {
    if (empty($this->data['fields'][$name]))
      return false;

    self::move_array_element($this->data['fields'], $name, $delta);
    return true;
  }

  // Перемещение элемента массива.
  private static function move_array_element(array &$array, $key, $offset)
  {
    $keys = array_keys($array);

    // Валидация.
    if (empty($key) or empty($array[$key]))
      return;

    // Пока сокращаем до одного шага в любую сторону.
    $offset = ($offset < 1) ? -1 : 1;

    // Определяем позицию перемещаемого элемента.
    if (($src = array_search($key, $keys)) === false)
      return;

    // Определяем позицию элемента, с которым производим обмен.
    $dst = $src + $offset;

    // Валидация.
    if ($dst < 0 or $dst >= count($keys))
      return;

    // Обмен.
    $old = $keys[$dst];
    $keys[$dst] = $keys[$src];
    $keys[$src] = $old;

    // Формирование нового массива.
    $data = array();
    foreach ($keys as $k)
      $data[$k] = $array[$k];

    // Замещаем.
    $array = $data;
  }

  // Сохранение фиксированных прав.
  public function setAccess(array $perms, $reset = true)
  {
    $perms['Schema Managers'] = array('r', 'u', 'd');
    return parent::setAccess($perms, $reset);
  }

  // Проверка прав на объект.  Менеджеры схемы всегда всё могут.
  public function checkPermission($perm)
  {
    if (mcms::user()->hasGroup('Schema Managers'))
      return true;
    return NodeBase::checkPermission($perm);
  }

  // РАБОТА С ФОРМАМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Forms

  public function formGet($simple = true)
  {
    $user = mcms::user();
    $user->checkGroup('Schema Managers');

    $form = parent::formGet($simple);

    if (null !== ($tab = $this->formGetFields()))
      $form->addControl($tab);

    if (null !== ($tab = $this->formGetWidgets()))
      $form->addControl($tab);

    $form->title = (null === $this->id)
      ? t('Добавление типа документа')
      : t('Редактирование типа "%title"', array('%name' => $this->name, '%title' => $this->title));

    return $form;
  }

  private function formGetFields()
  {
    $form = new FieldSetControl(array(
      'name' => 'fields',
      'label' => t('Поля'),
      ));

    foreach ($this->fields as $k => $v) {
      $field = new FieldControl(array(
        'name' => $k,
        'value' => 'node_content_fields',
        'label' => $v['label'],
        ));

      $form->addControl($field);
    }

    $form->addControl(new FieldControl(array(
      'name' => null,
      'value' => 'node_content_fields',
      )));

    return $form;
  }

  private function formGetWidgets()
  {
    $options = array();

    foreach (Node::find(array('class' => 'widget', 'classname' => array('DocWidget', 'ListWidget'))) as $w)
      if (substr($w->name, 0, 5) != 'Bebop')
        $options[$w->id] = $w->title;

    $tab = new FieldSetControl(array('name' => 'widgets', 'label' => t('Виджеты')));
    $tab->addControl(new SetControl(array(
      'value' => 'node_type_widgets',
      'label' => 'Обрабатываемые виджеты',
      'options' => $options,
      )));

    return $tab;
  }

  public function formGetData()
  {
    $data = parent::formGetData();
    $data['node_content_fields'] = $this->fields;
    $data['node_type_widgets'] = $this->linkListChildren('widget', true);

    return $data;
  }

  // Обрабатывает подключение виджетов и полей, остальное передаёт родителю.
  public function formProcess(array $data)
  {
    // Обновляем базовые свойства, типа имени и описания.
    parent::formProcess($data);

    // Теперь, когда имя типа известно, можно обновить поля.
    $this->updateFields($data);

    if (empty($this->fields)) {
      mcms::db()->rollBack();
      bebop_debug($this, $fields, $data);
      throw new InvalidArgumentException(t('Попытка очистить поля типа документа.'));
    }

    // Подключаем виджеты.
    if (mcms::user()->hasGroup('Developers'))
      $this->linkSetChildren(array_key_exists('node_type_widgets', $data) ? $data['node_type_widgets'] : array(), 'widget');
  }

  protected function updateFields(array $data)
  {
    $t = new TableInfo('node_'. $this->name);

    if (!$t->columnExists('rid'))
      $t->columnSet('rid', array(
        'type' => 'int(10) unsigned',
        'required' => true,
        'key' => 'pri',
        ));

    $old = empty($this->fields) ? array() : $this->fields;
    $new = empty($data['node_content_fields']) ? array() : $data['node_content_fields'];

    // Удаляем поля.
    foreach ($old as $k => $v) {
      if (!empty($v['indexed'])) {
        if (empty($new[$k]['indexed']) or !empty($new[$k]['delete']))
          $t->columnDel($k);
      }
    }

    // Обрабатываем новые поля.
    foreach ($new as $k => $v) {
      if (!empty($v['delete'])) {
        unset($new[$k]);
        continue;
      }

      if ('new-field' == $k) {
        if (empty($v['name'])) {
          unset($new[$k]);
          continue;
        } else {
          unset($new[$k]);
          $new[$k = $v['name']] = $v;

          self::checkFieldName($k);
        }
      }

      if (!empty($v['indexed'])) {
        if (!empty($v['type']) and class_exists($v['type'])) {
          $spec = array(
            'type' => call_user_func(array($v['type'], 'getSQL')),
            'required' => !empty($v['required']),
            'index' => 'mul',
            'default' => (isset($v['default']) and '' !== $v['default']) ? $v['default'] : null,
            );
          $t->columnSet($k, $spec);
        }
      }
    }

    $t->commit();

    $this->data['fields'] = $new;

    // Не дожидаемся сохранения извне, т.к. о нём могут забыть,
    // а структура таблиц у нас уже изменилась.
    $this->save();

    mcms::flush();
  }

  private static function checkFieldName($name)
  {
    $ok = true;

    if (strspn(strtolower($name), 'abcdefghijklmnopqrstuvwxyz0123456789_') != strlen($name))
      $ok = false;

    if ($ok and (substr($name, 0, 1) == '_' or substr($name, -1) == '_'))
      $ok = false;

    if ($ok and (false !== strstr($name, '__')))
      $ok = false;

    if (!$ok)
      throw new ValidationError('name', t("Имя поля может содержать только буквы латинского алфавита, арабские цифры и символ подчёркивания (\"_\"), вы ввели: %name.", array('%name' => $name)));
  }

  public function getAccess()
  {
    $data = parent::getAccess();

    if (null === $this->id) {
      $data['Visitors']['r'] = 1;
      $data['Content Managers']['c'] = 1;
      $data['Content Managers']['r'] = 1;
      $data['Content Managers']['u'] = 1;
      $data['Content Managers']['d'] = 1;
      $data['Schema Managers']['r'] = 1;
      $data['Schema Managers']['u'] = 1;
      $data['Schema Managers']['d'] = 1;
    }

    return $data;
  }

  public static function taskRun()
  {
    $count = 0;
    $sql = "SELECT x.id FROM node n INNER JOIN node__rev r ON r.rid = n.rid INNER JOIN node x ON x.class = r.name WHERE n.class = 'type' AND x.updated < n.updated AND n.deleted = 0 AND x.class NOT IN ('type', 'widget', 'domain', 'tag', 'user') LIMIT 10";

    while (count($ids = mcms::db()->getResultsV('id', $sql))) {
      $nodes = Node::find(array('id' => $ids));

      foreach ($nodes as $node) {
        $node->save();
        $count++;
      }
    }
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());

    $form->addControl(new EmailControl(array(
      'value' => 'config_from',
      'label' => t('Адрес отправителя'),
      'default' => mcms::config('mail_from'),
      )));

    return $form;
  }
};
