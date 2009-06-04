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

  public function __construct(PDO_Singleton $db, array $data = array())
  {
    parent::__construct($db, $data);
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

    parent::checkUnique('name', t('Тип документа со внутренним именем %name уже есть.', array('%name' => $this->name)));

    // Подгружаем поля, ранее описанные отдельными объектами (9.03 => 9.05).
    $this->backportLinkedFields();

    // Добавляем привычные поля, если ничего нет.
    if ($isnew and empty($this->fields))
      $this->fields = array(
        'name' => array(
          'type' => 'TextLineControl',
          'label' => t('Название'),
          'required' => true,
          'weight' => 10,
          ),
        'uid' => array(
          'type' => 'UserControl',
          'label' => t('Автор'),
          'required' => true,
          'weight' => 20,
          ),
        'created' => array(
          'type' => class_exists('DateTimeControl')
            ? 'DateTimeControl'
            : 'TextLineControl',
          'label' => t('Дата создания'),
          'weight' => 100,
          ),
        );

    // Всегда сохраняем без очистки.
    parent::save();

    $this->publish();

    // Обновляем тип документов, если он изменился.
    if (null !== $this->oldname and $this->name != $this->oldname) {
      $this->getDB()->exec("UPDATE `node` SET `class` = ? WHERE `class` = ?",
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

  public function publish()
  {
    $rc = parent::publish();
    $this->flush();
    return $rc;
  }

  public function unpublish()
  {
    $rc = parent::unpublish();
    $this->flush();
    return $rc;
  }

  public function delete()
  {
    // удалим связанные с этим типом документы
    $this->getDB()->exec("DELETE FROM `node` WHERE `class` = :type", array(':type' => $this->name));

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
    Schema::flush($this->name);
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

  public static function getInternal()
  {
    return array('type', 'tag', 'widget', 'domain', 'moduleinfo', 'file', 'user', 'group', 'comment', 'moduleinfo');
  }

  public static function getAccessible($mode = 'r')
  {
    $ctx = Context::last();
    $result = array();

    foreach (Node::find(array('class' => 'type', 'deleted' => 0), $ctx->db) as $type)
      if (null === $mode or $ctx->user->hasAccess('r', $type->name))
        $result[$type->name] = empty($type->title) ? $type->name : $type->title;

    asort($result);

    return $result;
  }

  public function getAllowedSections()
  {
    $list = array();
    foreach ($this->getLinkedTo('tag') as $node)
      $list[] = $node->id;
    return $list;
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

    $tmp = Node::create($this->name);

    if (in_array($this->name, self::getInternal()))
      $links['delete'] = null;

    if ($tmp->canEditFields())
      $links['fields'] = array(
        'title' => t('Настроить поля'),
        'href' => 'admin/structure/fields?type=' . $this->name
          . '&destination=CURRENT',
        );
    else {
      if (isset($links['clone']))
        unset($links['clone']);
      if (isset($links['delete']))
        unset($links['delete']);
    }

    $links['touch'] = array(
      'title' => t('Обновить документы'),
      'href' => 'admin/structure/types/refresh?type=' . $this->name
        . '&destination=CURRENT',
      'description' => t('Пересохраняет все документы этого типа, обновляет XML.'),
      );

    $links['access'] = array(
      'title' => t('Изменить права'),
      'href' => 'admin/structure/access?type=' . urlencode($this->name)
        . '&destination=CURRENT',
      );

    return $links;
  }

  public function getFormFields()
  {
    $schema = new Schema(array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Внутреннее имя'),
        'description' => t('Только небольшие латинские буквы, арабские цифры и прочерки.'),
        'required' => true,
        'group' => t('Основные свойства'),
        'weight' => 10,
        ),
      'title' => array(
        'type' => 'TextLineControl',
        'label' => t('Отображаемое имя'),
        'description' => t('Название, понятное простому человеку, например: "Статья", или "Запись на приём".'),
        'required' => true,
        'group' => t('Основные свойства'),
        'weight' => 10,
        ),
      'description' => array(
        'type' => 'TextAreaControl',
        'label' => t('Описание'),
        'description' => t('Помогает неопытным пользователям ориентироваться при добавлении документов.'),
        'group' => t('Основные свойства'),
        'weight' => 20,
        ),
      'isdictionary' => array(
        'type' => 'BoolControl',
        'value' => 'isdictionary',
        'label' => t('Тип является справочником'),
        'weight' => 30,
        ),
      'perms' => array(
        'type' => 'AccessControl',
        'group' => t('Права для групп'),
        ),
      'tags' => array(
        'type' => 'SectionsControl',
        'group' => t('Разрешённые разделы'),
        'dictionary' => 'tag',
        ),
      ));

    if (empty($this->id) or $this->name != 'type')
      $schema['isdictionary'] = new BoolControl(array(
        'value' => 'isdictionary',
        'label' => t('Тип является справочником'),
        'volatile' => true,
        ));

    $tmp = Node::create($this->name);
    if (!$tmp->canEditFields() and isset($schema['fields']))
      unset($schema['fields']);
    if (!$tmp->canEditSections())
      unset($schema['tags']);

    return $schema;
  }

  public static function getList()
  {
    return Node::find(array(
      'class' => 'type',
      'deleted' => 0,
      ));
  }

  /**
   * Возвращает список справочников.
   */
  public static function getDictionaries()
  {
    static $result = null;

    if (null === $result) {
      $result = array();

      foreach (Node::find(array('class' => 'type')) as $t)
        if ($t->isdictionary and $t->name != 'field')
          $result[$t->name] = $t->title;
    }

    return $result;
  }

  public function getExtraXMLContent()
  {
    if (!Node::create($this->name)->canEditFields())
      return html::em('fields', array(
        'static' => true,
        ));

    $this->backportLinkedFields();

    $fields = '';

    foreach ((array)$this->fields as $k => $v) {
      if (class_exists($v['type'])) {
        $info = call_user_func(array($v['type'], 'getInfo'));
        if (isset($info['name']))
          $v['typeName'] = $info['name'];
      }
      if (empty($v['weight']))
        $v['weight'] = 50;
      if (Node::isBasicField($k))
        $v['indexed'] = true;
      $fields .= html::em('field', array('name' => $k) + $v);
    }

    return html::wrap('fields', $fields);
  }

  public function backportLinkedFields()
  {
    if (!empty($this->fields))
      return;

    $fields = Node::find(array(
      'class' => 'field',
      'deleted' => 0,
      'tags' => $this->id,
      ), $this->getDB());
    if (!empty($fields)) {
      $result = array();
      foreach ($fields as $field) {
        foreach (array('label', 'type', 'weight', 'indexed', 'required', 'description', 'group') as $key)
          if (!empty($field->$key))
            $result[$field->name][$key] = $field->$key;
      }
      $this->fields = $result;
      return true;
    }
  }

  /**
   * Запрещаем редактировать поля этого метатипа. Часто пользователи
   * добавляют сюда всякий хлам, не очень понимая, что делают.
   */
  public function canEditFields()
  {
    return false;
  }

  public function getName()
  {
    return $this->title
      ? $this->title
      : $this->name;
  }

  public function getListURL()
  {
    return 'admin/structure/types';
  }

  public function getPreviewXML(Context $ctx)
  {
    $result = parent::getPreviewXML($ctx);

    if (!$this->published) {
      $message = t('Документы этого (скрытого) типа не отображаются в обычном <a href="@url1">списке документов</a>, их не предлагают в стандартной <a href="@url2">форме создания документа</a>.', array(
        '@url1' => 'admin/content/list',
        '@url2' => 'admin/create',
        ));
      $result .= html::em('field', array(
        'title' => t('Комментарий'),
        ), html::em('value', html::cdata($message)));
    }

    $count = Node::count(array(
      'class' => $this->name,
      'deleted' => 0,
      ), $ctx->db);
    if ($count) {
      $message = t('%count документов (<a href="@url">список</a>)', array(
        '%count' => $count,
        '@url' => Node::create($this->name)->getListURL(),
        ));
      $result .= html::em('field', array(
        'title' => t('Статистика'),
        ), html::em('value', html::cdata($message)));
    }

    return $result;
  }
};
