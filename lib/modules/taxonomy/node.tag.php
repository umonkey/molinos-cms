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
    if ($this->isNew()) {
      if (!$this->parent_id) {
        try {
          Node::load(array(
            'class' => 'tag',
            'parent_id' => null,
            'deleted' => 0,
            ), $this->getDB());
          throw new RuntimeException(t('Нельзя создать новый корневой раздел.'));
        } catch (ObjectNotFoundException $e) {
        }
      }

      // Копируем родительскую привязку к типам.
      $this->onSave("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) "
        ."SELECT %ID%, `nid`, `key`, `order` FROM `node__rel` "
        ."WHERE `tid` = ? AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'type')",
        array($this->parent_id));

      // Копируем родительские права.
      $this->onSave("INSERT INTO `node__access` (`nid`, `uid`, `p`) "
        . "SELECT %ID%, `uid`, `p` FROM `node__access` WHERE `nid` = ?",
        array($this->parent_id));
    }

    return parent::save();
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

  public function getListURL()
  {
    return 'admin/structure/taxonomy';
  }

  /**
   * Дополнительные действия для предварительного просмотра.
   */
  public function getActionLinks()
  {
    return array_merge(parent::getActionLinks(), array(
      'find' => array(
        'title' => t('Показать документы'),
        'href' => 'admin/content/list?search=tags%3A' . $this->id,
        ),
      ));
  }

  /**
   * Возвращает XML ветки.
   *
   * Форсирует формирование дерева, т.к. родительская реализация проверяет
   * наличие у объекта границ и отказывается формировать XML при их отсутствии,
   * что приводит к невозможности сформировать дерево при наличии только одного
   * раздела.
   */
  public function getTreeXML($published = true)
  {
    return parent::getTreeXML($published, true);
  }
};
