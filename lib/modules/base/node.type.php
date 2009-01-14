<?php
/**
 * Искуственный интеллект типа документа.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Искуственный интеллект типа документа.
 *
 * @package mod_base
 * @subpackage Types
 */
class TypeNode extends Node implements iContentType
{
  // Устанавливается при изменении внутреннего имени.  После сохранения все
  // документы этого типа обновляются.
  private $oldname = null;

  public function __construct(array $data)
  {
    parent::__construct($data);

    $this->oldname = $this->name;
  }

  public function save()
  {
    $isnew = (null === $this->id);

    if (empty($this->title))
      $this->title = $this->name;

    if (empty($this->name))
      throw new ValidationException('name', t('Внутреннее имя типа '
        .'не может быть пустым.'));
    elseif (strspn(strtolower($this->name), 'abcdefghijklmnopqrstuvwxyz0123456789_') != strlen($this->name))
      throw new ValidationException('name', t('Внутреннее имя типа может '
        .'содержать только латинские буквы, арабские цифры и прочерк.'));

    $this->data['published'] = 1;

    parent::checkUnique('name', t('Тип документа со внутренним именем %name уже есть.', array('%name' => $this->name)));

    // Всегда сохраняем без очистки.
    parent::save();

    $this->updateTable();

    // Обновляем тип документов, если он изменился.
    if (null !== $this->oldname and $this->name != $this->oldname) {
      mcms::db()->exec("UPDATE `node` SET `class` = ? WHERE `class` = ?",
        array($this->name, $this->oldname));
    }

    // Обновляем кэш.
    $this->flush();
  }

  public function duplicate($parent = null)
  {
    $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();
    $this->oldname = null;

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
    // FIXME: удалить
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

  public function recreateIdxTable($tblname)
  {
    if ($tblname != $this->name)
      throw new InvalidArgumentException(t('Пересоздание полей не в том объекте: %a vs. %b', array(
        '%a' => $this->name,
        '%b' => $tblname,
        )));

    mcms::db()->exec("DROP TABLE IF EXISTS node__idx_{$tblname}");

    // $result = Node::create($tblname)->schema();
    // $this->fields = $result['fields'];
    // $this->name = $tblname;
    return $this->updateTable();
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

  public function getFormTitle()
  {
    if ($this->isdictionary)
      return $this->id
        ? t('Свойства справочника «%name»', array('%name' => $this->name))
        : t('Добавление нового справочника');

    return $this->id
      ? t('Настройка типа «%name»', array('%name' => $this->name))
      : t('Добавление нового типа документа');
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

      if (!empty($v['indexed'])) {
        self::checkFieldName($k);

        if (!empty($v['type']) and class_exists($v['type'])) {
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
      return false;

    $t->commit();

    // Значения в индексной таблице обновляются по cron
    mcms::db()->exec("DELETE FROM `node__idx_{$this->name}`");

    return true;
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

    foreach (Node::find(array('class' => 'type', 'deleted' => 0)) as $type)
      if (null === $mode or mcms::user()->hasAccess('r', $type->name))
        $result[$type->name] = empty($type->title) ? $type->name : $type->title;

    asort($result);

    return $result;
  }

  public function getAllowedSections()
  {
    if (count($ids = $this->linkListParents('tag', true)))
      return Node::find(array('id' => $ids));
    return array();
  }

  /**
   * Возвращает список разделов, в которые можно помещать документ.
   *
   * Базовая реализация проверяет права, однако TypeNode — особый
   * случай, и привязка к разделам здесь — определение привязки
   * для документов этого типа, поэтому нужно разрешить работу
   * со _всеми_ разделами, что мы и делаем, возвращая NULL.
   */
  public function getEnabledSections()
  {
    return null;
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();

    if (in_array($this->name, self::getInternal()))
      $links['delete'] = null;

    return $links;
  }

  public function getFormFields()
  {
    $schema = $this->getSchema();

    if (empty($this->id) or $this->name != 'type')
      $schema['isdictionary'] = new BoolControl(array(
        'value' => 'isdictionary',
        'label' => t('Тип является справочником'),
        'volatile' => true,
        ));

    $schema['fields'] = new FieldControl(array(
      'value' => 'fields',
      'label' => null,
      'group' => t('Поля'),
      ));

    return $schema;
  }

  public static function getDefaultSchema()
  {
    return array(
      'perms' => array(
        'type' => 'AccessControl',
        'label' => t('Права для групп'),
        'group' => t('Доступ'),
        'volatile' => true,
        ),
      'tags' => array(
        'type' => 'SectionsControl',
        'label' => t('Документы этого типа можно помещать в разделы'),
        'group' => t('Разделы'),
        'dictionary' => 'tag',
        'volatile' => true,
        ),
      'notags' => array(
        'deprecated' => true,
        ),
      'hasfiles' => array(
        'deprecated' => true,
        ),
      );
  }

  public static function getList()
  {
    return Node::find(array(
      'class' => 'type',
      'deleted' => 0,
      ));
  }
};
