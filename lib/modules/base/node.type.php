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

  public function __construct(NodeStub $stub)
  {
    parent::__construct($stub);

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
    $result = array();

    foreach (Node::find($this->getDB(), array('class' => 'type', 'deleted' => 0)) as $type)
      if (null === $mode or Context::last()->user->hasAccess('r', $type->name))
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

    if (!empty($this->id)) {
      $tmp = Node::create($this->name);
      if ($tmp->canEditFields()) {
        $schema['fields'] = new SetControl(array(
          'value' => 'fields',
          'label' => t('Используемые поля'),
          'dictionary' => 'field',
          'field' => 'label',
          'required' => true,
          'group' => t('Поля'),
          ));
      }
    }

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
    return Node::find(Context::last()->db, array(
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

      foreach (Node::find(Context::last()->db, array('class' => 'type')) as $t)
        if ($t->isdictionary and $t->name != 'field')
          $result[$t->name] = $t->title;
    }

    return $result;
  }
};
