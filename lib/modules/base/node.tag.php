<?php
/**
 * Тип документа «tag» — раздел сайта.
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
 * Тип документа «tag» — раздел сайта.
 *
 * Используется для формирования структуры данных сайта (не путать со структурой
 * страниц, которая описывается с помощью DomainNode).
 *
 * @package mod_base
 * @subpackage Types
 */
class TagNode extends Node implements iContentType
{
  /**
   * Сохранение раздела.
   *
   * При сохранении раздела с пустым полем parent_id, в него подставляется код
   * существующего корневого раздела (если он существует).  Привязка к типам
   * документов копируется из родительского раздела.
   *
   * @return Node ссылка на себя (для построения цепочек).
   */
  public function save()
  {
    $isnew = empty($this->id);

    if (null === $this->parent_id) {
      try {
        $node = Node::load(array('class' => 'tag', 'parent_id' => null, 'deleted' => 0));
        $this->data['parent_id'] = $node->id;
      } catch (ObjectNotFoundException $e) {
      }
    }

    parent::save();

    // При добавлении раздела копируем родительский список типов.
    if ($isnew and !empty($this->parent_id))
      Context::last()->db->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) "
        ."SELECT :me, `nid`, `key`, `order` FROM `node__rel` "
        ."WHERE `tid` = :parent AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'type')",
        array(':me' => $this->id, ':parent' => $this->parent_id));

    return $this;
  }

  public function duplicate($parent = null)
  {
    if (empty($this->parent_id))
      throw new RuntimeException(t('Корневой раздел клонировать нельзя.'));

    return parent::duplicate($parent);
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Редактирование раздела «%name»', array('%name' => $this->name))
      : t('Добавление нового раздела');
  }

  private static function haveRoot()
  {
    return Node::count(array('class' => 'tag', 'parent_id' => null, 'deleted' => 0));
  }

  public function getFormFields()
  {
    $schema = new Schema(array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Название раздела'),
        'group' => t('Основные свойства'),
        'weight' => 10,
        ),
      'description' => array(
        'type' => 'TextAreaControl',
        'label' => t('Описание'),
        'group' => t('Основные свойства'),
        'weight' => 20,
        ),
      'perms' => array(
        'type' => 'AccessControl',
        'volatile' => true,
        'group' => t('Права на добавление документов'),
        'columns' => array('c'),
        'description' => t('Пользователи из отмеченных групп смогут добавлять документы в этот раздел.'),
        ),
      ));

    return $schema;
  }
};
