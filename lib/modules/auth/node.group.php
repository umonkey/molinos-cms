<?php
/**
 * Тип документа "group" — группа пользователей.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Тип документа "group" — группа пользователей.
 *
 * @package mod_base
 * @subpackage Types
 */
class GroupNode extends Node implements iContentType
{
  /**
   * Сохранение группы.
   *
   * Проверяет имя группы на уникальность.
   *
   * @return Node сохранённый объект.
   */
  public function save()
  {
    if (empty($this->login))
      $this->login = $this->name;

    parent::checkUnique('name', t('Группа с таким именем уже существует'));

    return parent::save();
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Редактирование группы «%name»', array('%name' => $this->name))
      : t('Добавление новой группы');
  }

    public function getFormFields()
    {
      return new Schema(array(
        'name' => array (
          'label' => 'Название',
          'type' => 'TextLineControl',
          'required' => true,
          'weight' => 10,
          ),
        'description' => array (
          'label' => 'Описание',
          'type' => 'TextAreaControl',
          'weight' => 20,
          ),
        'typerms' => array(
          'type' => 'AccessRevControl',
          'group' => t('Доступ к типам документов'),
          // 'label' => t('Доступ к типам документов'),
          'dictionary' => 'type',
          'weight' => 30,
          ),
        'tagperms' => array(
          'type' => 'AccessRevControl',
          'group' => t('Доступ на публикацию в разделы'),
          // 'label' => t('Доступ к разделам'),
          'dictionary' => 'tag',
          'columns' => array('c'),
          'weight' => 40,
          ),
        'users' => array(
          'type' => 'SetControl',
          'label' => t('В группу входят'),
          'dictionary' => 'user',
          'group' => t('Участники'),
          'weight' => 50,
          ),
        ));
    }

  public function getListURL()
  {
    return 'admin/access/groups';
  }

  public function canEditFields()
  {
    return false;
  }

  public function getActionLinks()
  {
    $links = parent::getActionLinks();

    $links['find'] = array(
      'href' => 'admin/access/users?search=tags%3A' . $this->id,
      'title' => t('Показать пользователей'),
      );

    return $links;
  }
}
