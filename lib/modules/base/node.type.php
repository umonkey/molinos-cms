<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TypeNode extends Node implements iContentType, iScheduler, iModuleConfig
{
  private $oldfields = null;

  public function __construct(array $data)
  {
    parent::__construct($data);

    if (empty($this->data['id']) and empty($this->data['fields'])) {
      $this->data['fields'] = array(
        'name' => array(
          'label' => 'Заголовок',
          'type' => 'TextLineControl',
          'required' => true,
          'indexed' => true,
          ),
        'teaser' => array(
          'label' => 'Вступление',
          'type' => 'TextAreaControl',
          'required' => false,
          'description' => 'Краткое содержание, 1-2 предложения.  Выводится в списках документов, RSS итд.',
          ),
        'text' => array(
          'label' => 'Текст',
          'type' => 'TextHTMLControl',
          'required' => true,
          ),
        'created' => array(
          'label' => 'Дата добавления',
          'type' => 'DateTimeControl',
          'required' => false,
          'indexed' => true,
          ),
        );
    }
  }

  // Инсталляция типов документов.
  public static function install()
  {
    // Запрашиваем схему с перезагрузкой.
    $schema = self::getSchema(null, true);

    // Инсталлируем типы с доступной реализацией.  Для этого используем запрос
    // схемы конкретного типа.
    foreach (mcms::getImplementors('iContentType') as $class) {
      if ('Node' == substr($class, -4) and strlen($type = strtolower(substr($class, 0, -4)))) {
        if (0 == Node::count(array('class' => 'type', 'name' => $type))) {
          $tmp = TypeNode::getSchema($type);
          $tmp['published'] = true;
          $tmp['name'] = $type;

          $node = Node::create('type', $tmp);
          $node->save();
        }
      }
    }
  }

  public function save()
  {
    $isnew = (null === $this->id);

    if (empty($this->title))
      $this->title = $this->name;

    $this->data['published'] = 1;

    parent::checkUnique('name', t('Тип документа со внутренним именем %name уже есть.', array('%name' => $this->name)));

    // Всегда сохраняем без очистки.
    parent::save();

    $this->updateTable();

    // Обновляем кэш.
    $this->flush();
  }

  public function duplicate($parent = null)
  {
    $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();

    parent::duplicate($parent);

    $this->flush();
  }

  public function publish($rev = null)
  {
    $rc = parent::publish($rev);
    $this->flush();
    return $rc;
  }

  public function unpublish()
  {
    $rc = parent::unpublish($rev);
    $this->flush();
    return $rc;
  }

  public function delete()
  {
    $t = new TableInfo('node__idx_'. $this->name);
    if ($t->exists()) {
      $t->delete();
    }

    // удалим связанные с этим типом документы
    mcms::db()->exec("DELETE FROM `node` WHERE `class` = :type", array(':type' => $this->name));

    // удалим бесхозные ревизии
    mcms::db()->exec("DELETE FROM `node__rev` WHERE `nid` NOT IN (SELECT `id` FROM `node`)");

    $rc = parent::delete();

    $this->erase();

    $this->flush();
    return $rc;
  }

  public function undelete()
  {
    $rc = parent::undelete();
    $this->flush();
    return $rc;
  }

  private function flush()
  {
    self::getSchema(null, true);
  }

  public function __set($k, $v)
  {
    // Отслеживаем изменения в полях.
    if ('fields' === $k) {
      if (null === $this->oldfields)
        $this->oldfields = $this->fields;
    }

    parent::__set($k, $v);
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
    $fields = $this->fields;

    if (!empty($fields[$name])) {
      ksort($fields[$name]);
      ksort($data);

      if (serialize($fields[$name]) === serialize($data))
        return false;
    }

    $fields[$name] = $data;
    $this->fields = $fields;

    return true;
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
            if ($field['type'] == 'AttachmentControl') {
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
    static $lock = false;

    if (false !== $lock)
      mcms::fatal('Рекурсия при вызове TypeNode::getSchema()!');

    $lock = true;

    if ($reload or (!is_array($result = mcms::pcache('schema')) or empty($result))) {
      $result = array();

      foreach (NodeBase::dbRead("SELECT `n`.`id` as `id`, `n`.`class` AS `class`, `n`.`rid` as `rid`, `r`.`name` as `name`, `r`.`data` as `data` FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` WHERE `n`.`class` = 'type' AND `n`.`deleted` = 0") as $n)
        $result[$n->name] = $n->getRaw();

      mcms::pcache('schema', $result);
    }

    if ($name !== null and empty($result[$name])) {
      if (null !== ($def = Node::create($name)->getDefaultSchema())) {
        try {
          $tmp = Node::create('type', $def);
          $tmp->name = $name;
          // $tmp->save();

          $result[$name] = array_merge(array('id' => $tmp->id), $def);

          mcms::pcache('schema', $result);
        } catch (ValidationException $e) {
        } catch (Exception $e) {
          $lock = false;
          throw $e;
        }
      }
    }

    $lock = false;

    if (null !== $name)
      return empty($result[$name]) ? null : $result[$name];

    return $result;
  }

  public function recreateIdxTable($tblname)
  {
    mcms::db()->exec("DROP TABLE IF EXISTS node__idx_{$tblname}");
    $result = $this->getSchema($tblname);
    $this->fields = $result['fields'];
    $this->name = $tblname;
    $this->updateTable();
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

  // РАБОТА С ФОРМАМИ.
  // Документация: http://code.google.com/p/molinos-cms/wiki/Forms

  public function formGet($simple = true)
  {
    $user = mcms::user();

    if (!mcms::user()->hasAccess('u', 'type'))
      throw new ForbiddenException();

    $form = parent::formGet($simple);

    if (null !== ($tmp = $form->findControl('node_tags')))
      $tmp->label = t('Документы этого типа можно помещать в разделы');

    if (null !== ($tab = $this->formGetFields()))
      $form->addControl($tab);

    if (null !== ($tab = $this->formGetWidgets()))
      $form->addControl($tab);

    $form->title = (null === $this->id)
      ? t('Добавление типа документа')
      : t('Редактирование типа "%title"', array('%name' => $this->name, '%title' => $this->title));

    if (null !== ($tmp = $this->getAccessTab()))
      $form->addControl($tmp);

    return $form;
  }

  private function getAccessTab()
  {
    $user = mcms::user();

    $url = new url();

    // Добавляем вкладку с правами.
    if ($user->hasAccess('u', 'user') and 'admin' == $url->path) {
      $options = array(0 => t('Анонимные пользователи'));

      foreach ($acc = $this->getAccess() as $k => $v)
        $options[$k] = empty($v['name']) ? $k : $v['name'];

      $tab = new FieldSetControl(array(
        'name' => 'access',
        'label' => t('Доступ'),
        'value' => 'tab_access',
        ));
      $tab->addControl(new InfoControl(array(
        'text' => t('Укажите группы пользователей, которые могут создавать (C), читать (R), изменять (U) и удалять (D) документы этого типа.'),
        'url' => 'http://code.google.com/p/molinos-cms/wiki/Permissions',
        )));
      $tab->addControl(new HiddenControl(array(
        'value' => 'reset_access',
        )));
      $tab->addControl(new AccessControl(array(
        'value' => 'node_access',
        'options' => $options,
        'label' => t('Доступ разрешён группам'),
        )));

      return $tab;
    }
  }

  private function formGetFields()
  {
    $form = new FieldSetControl(array(
      'name' => 'fields',
      'label' => t('Поля'),
      ));

    $id = 1;

    if (!empty($this->fields)) {
      foreach ($this->fields as $k => $v) {
        $field = new FieldControl(array(
          'id' => 'field'. $id++,
          'name' => $k,
          'value' => 'node_content_fields',
          'label' => empty($v['label']) ? $k : $v['label'],
         ));
        $form->addControl($field);
      }
    }

    $form->addControl(new FieldControl(array(
      'id' => 'field'. $id++,
      'name' => null,
      'value' => 'node_content_fields',
      )));

    return $form;
  }

  private function formGetWidgets()
  {
    $options = array();

    $filter = array(
      'class' => 'widget',
      '#sort' => array(
        'name' => 'asc',
        ),
      );

    foreach (Node::find($filter) as $w)
      if (in_array($w->classname, array('DocWidget', 'ListWidget')))
        $options[$w->id] = $w->title;

    if (!empty($options)) {
      $tab = new FieldSetControl(array(
        'name' => 'widgets',
        'label' => t('Виджеты'),
        'intro' => t('Укажите виджеты, которые будут работать с документами этого типа.'),
        'value' => 'tab_widgets',
        ));

      $tab->addControl(new SetControl(array(
        'value' => 'node_type_widgets',
        'label' => 'Обрабатываемые виджеты',
        'options' => $options,
        )));

      return $tab;
    }
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
    $there_were_fields = !empty($this->fields);

    if (!isset($this->id) and !empty($data['node_content_isdictionary'])) {
      $this->data['isdictionary'] = true;
      $this->data['published'] = true;
    }

    // TODO: этот блок раньше был ПОД formProcess(), почему?  Были какие-то
    // с ним проблемы, я не помню какие.  Надо проверить ещё раз, везде ли
    // сейчас работает, и поправить, если надо.  И задокументировать! — hex, 12.05.08
    $this->updateFields($data);

    // Обновляем базовые свойства, типа имени и описания.
    parent::formProcess($data);

    if ($there_were_fields and empty($this->fields)) {
      mcms::db()->rollBack();
      throw new InvalidArgumentException(t('Попытка очистить поля типа документа.'));
    }

    // FIXME: у нас из parent::formProcess и так уже вызывается
    // TypeNode->save. Насколько необходим повторный вызов?
    $this->save();

    // Подключаем виджеты.
    if (mcms::user()->hasAccess('u', 'widget'))
      $this->linkSetChildren(array_key_exists('node_type_widgets', $data) ? $data['node_type_widgets'] : array(), 'widget');
  }

  protected function updateFields(array $data)
  {
    $fields = array();

    if (empty($data['node_content_fields']) or !is_array($data['node_content_fields']))
      throw new ValidationException(t('Поля документа не заполнены.'));

    foreach ($data['node_content_fields'] as $f) {
      if (!empty($f['name']) and empty($f['delete'])) {
        if (strspn(mb_strtolower($f['name']), '0123456789abcdefghijklmnopqrstuvwxyz_') != strlen($f['name']))
          throw new ValidationException('Имя поля может содержать только цифры, буквы и прочерк.');

        if (array_key_exists(strtolower($f['name']), $fields))
          throw new ValidationException(t('Поле %name описано дважды.', array('%name' => $f['name'])));

        foreach ($f as $k => $v)
          if (empty($v))
            unset($f[$k]);

        $fields[$f['name']] = $f;
        unset($fields[$f['name']]['name']);
      }
    }

    $this->fields = $fields;
  }

  public function updateTable()
  {
    //mcms::user()->checkAccess('u', 'type');

    $t = new TableInfo('node__idx_'. $this->name);

    if (!$t->columnExists('id'))
      $t->columnSet('id', array(
        'type' => 'int(10)',
        'required' => true,
        'key' => 'pri',
        ));

    // Добавляем новые поля.
    foreach ((array)$this->fields as $k => $v) {
      if (self::isReservedFieldName($k))
        $v['indexed'] = false;

      elseif (!empty($v['indexed']) and empty($old[$k]['indexed'])) {
        self::checkFieldName($k);

        if (!empty($v['type']) and mcms::class_exists($v['type'])) {
          $spec = array(
            'type' => call_user_func(array($v['type'], 'getSQL')),
            'required' => false, // !empty($v['required']),
            'key' => 'mul',
            'default' => (isset($v['default']) and '' !== $v['default']) ? $v['default'] : null,
            );
          $t->columnSet($k, $spec);
        }
      }
    }

    // Удалим ненужные индексы.
    foreach (array_keys($t->getColumns()) as $idx) {
      if ($idx != 'id' and empty($this->fields[$idx]['indexed']))
        $t->columnDel($idx);
    }

    // Если таблица создаётся, и колонка всего одна — rid — пропускаем.
    if (!$t->exists() and $t->columnCount() == 1 and $t->columnExists('id'))
      return;

    $t->commit();

    //Значения в индексной таблице обновляются по cron
    mcms::db()->exec("DELETE FROM `node__idx_{$this->name}`");
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
      throw new ValidationException('name', t("Имя поля может содержать только буквы латинского алфавита, арабские цифры и символ подчёркивания (\"_\"), вы ввели: %name.", array('%name' => $name)));
  }

  public static function taskRun()
  {
    $count = 0;
    $sql = "SELECT `x`.`id` AS `id` FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` INNER JOIN `node` `x` ON `x`.`class` = `r`.`name` WHERE `n`.`class` = 'type' AND `x`.`updated` < `n`.`updated` AND `n`.`deleted` = 0 AND `x`.`class` NOT IN ('type', 'widget', 'domain', 'tag', 'user') LIMIT 10";

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

  public static function hookPostInstall()
  {
  }

  public static function isReservedFieldName($field)
  {
    return in_array($field, self::getReservedNames());
  }

  public static function getInternal()
  {
    return array('type', 'tag', 'widget', 'domain', 'moduleinfo', 'file', 'user', 'group', 'comment', 'moduleinfo');
  }

  public static function getReservedNames()
  {
    return array(
      'id',
      'nid',
      'rid',
      'parent_id',
      'class',
      'left',
      'right',
      'created',
      'updated',
      'lang',
      'uid',
      'name',
      'data',
      'published',
      );
  }

  public static function getAccessible($mode = 'r')
  {
    $result = array();

    foreach (self::getSchema() as $k => $v)
      if (mcms::user()->hasAccess('r', $k))
        $result[$k] = empty($v['title']) ? $k : $v['title'];

    asort($result);

    return $result;
  }
};
