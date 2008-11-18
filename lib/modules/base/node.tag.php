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
      mcms::db()->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) "
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

  // Возвращает список существующих разделов, в виде плоского списка
  // с элементом depth, для рендеринга в виде дерева.
  /**
   * Возвращает полный список разделов.
   *
   * Используется для построения плоских списков разделов, например — в
   * выпадающих списках.
   *
   * @see Node::getChildren()
   *
   * @param string $mode передаётся в Node::getChildren().
   *
   * @param array $options дополнительные параметры, передаются в
   * Node::getChildren().
   *
   * @return array массив описаний разделов.
   */
  public static function getTags($mode, array $options = array())
  {
    $result = array();

    if (is_array($cached = mcms::cache($ckey = 'tags:' . $mode)))
      return $cached;

    // Загружаем все корневые разделы (в нормальных условиях такой должен быть один,
    // но на случае ошибок в БД мы всё таки даём возможность работать с ошибочными
    // разделами).
    foreach (Node::find(array('class' => 'tag', 'parent_id' => null)) as $node) {
      if ($mode == 'select') {
        $node->loadChildren(null, true);
        foreach ($node->getChildren($mode, $options) as $k => $v)
          $result[$k] = $v;
       } else {
        $result = array_merge($result, $node->getChildren($mode, $options));
       }
    }

    mcms::cache($ckey, $result);

    return $result;
  }

  /**
   * Возвращает форму для редактирования раздела.
   *
   * Единственное изменение, вносимое в полученную от родителя форму —
   * человеческий заголовок.
   *
   * @param bool $simple определяет, нужно ли возвращать дополнительные вкладки
   * (историю изменений, файлы итд).
   *
   * @return Form описание формы.
   */
  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    $form->title = (null === $this->id)
      ? t('Добавление нового раздела')
      : t('Редактирование раздела "%name"', array('%name' => $this->name));

    return $form;
  }

  private static function haveRoot()
  {
    return Node::count(array('class' => 'tag', 'parent_id' => null, 'deleted' => 0));
  }

  protected function getDefaultSchema()
  {
    return array(
      'perms' => array(
        'type' => 'AccessControl',
        'label' => t('Права на раздел'),
        'volatile' => true,
        'group' => t('Доступ'),
        'columns' => array('c'),
        'description' => t('Указанные группы смогут добавлять документы в этот раздел.'),
        ),
      );
  }
};
