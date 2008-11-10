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

  /**
   * Клонирование группы.
   *
   * Добавляет к имени группы немного цифр.
   *
   * @return Node новая группа.
   */
  public function duplicate()
  {
    $this->login = preg_replace('/_[0-9]+$/', '', $this->login) .'_'. ($rand = rand());
    $this->name = preg_replace('/ \([0-9]+\)$/', '', $this->name) .' ('. $rand .')';

    return parent::duplicate();
  }

  /**
   * Возвращает форму для редактирования группы.
   *
   * Добавляет в форму вкладки со списком пользователей и типов документов, к
   * которым у группы есть доступ.  Динамически меняет заголовок формы (делая
   * его читабельным).
   *
   * @return Form полученная от родителя форма с парой новых вкладок.
   *
   * @param bool $simple передаётся родителю, локально не используется.
   */
  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    $form->title = (null === $this->id)
      ? t('Добавление новой группы')
      : t('Редактирование группы %name', array('%name' => $this->name));

    return $form;
  }

  protected function getDefaultSchema()
  {
    return array(
      'name' => array (
        'label' => 'Название',
        'type' => 'TextLineControl',
        'required' => true,
        ),
      'description' => array (
        'label' => 'Описание',
        'type' => 'TextAreaControl',
        ),
      'users' => array(
        'type' => 'SetControl',
        'label' => t('В группу входят'),
        'dictionary' => 'user',
        'group' => t('Участники'),
        ),
      'typerms' => array(
        'type' => 'AccessRevControl',
        'group' => t('Доступ'),
        'label' => t('Доступ к типам документов'),
        'dictionary' => 'type',
        ),
      );
  }
}
