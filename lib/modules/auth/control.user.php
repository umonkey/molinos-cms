<?php
/**
 * Контрол для сбора информации об авторе документа.
 *
 * При сохранении нового документа делает текущего пользователя
 * его автором.  Если разрешено администратором и запрошено пользователем,
 * документ создаётся анонимно.  Изменение информации об авторе стандартными
 * средствами невозможно.
 *
 * @author Justin Forest <justin.forest@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html
 */

class UserControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Информация о пользователе'),
      );
  }

  /**
   * Отключает индексирование, используем связку с нодой.
   */
  public function getSQL()
  {
    return false;
  }

  /**
   * Возвращает XML для отображения контрола.
   */
  public function getXML($data)
  {
    // Существующий объект, ничего не делаем.
    if (!empty($data->id))
      return;

    $user = Context::last()->user;

    // Пользователь залогинен, делать нечего.
    if (!$this->required and $user->id)
      return parent::wrapXML(array(
        'type' => 'checkbox',
        'title' => t('Опубликовать анонимно (а не как %name)', array(
          '%name' => $user->getNode()->getName(),
          )),
        ));
    elseif (!$user->id)
      return parent::wrapXML(array(
        'type' => 'text',
        'title' => t('Имя или ник'),
        ));
  }

  /**
   * Сохранение введённого значения.
   *
   * Если объект не новый — у него есть id — ничего не происходит.  Для новых
   * объектов сохраняется информация о пользователе только если он не попросил
   * анонимности.
   */
  public function set($value, &$node)
  {
    if ($node->id)
      return;

    $user = Context::last()->user;

    // Анонимность разрешена и запрошена.
    if (!$this->required and $user->id and $value)
      return;

    // Сохраняем информацию.
    if ($user->id)
      $node->{$this->value} = Context::last()->user->getNode();
    else
      $node->{$this->value} = $value;
  }

  /**
   * Возвращает XML код поля.
   */
  public function format($value, $em)
  {
    if (is_object($value) and $value->id)
      return html::em($em, array(
        'id' => $value->id,
        'name' => $value->getName(),
        'email' => $value->email,
        ));
    elseif (is_string($value))
      return html::em($em, array(
        'name' => $value,
        ));
  }

  /**
   * Возвращает информацию для предварительного просмотра.
   */
  public function preview($node)
  {
    $value = $node->{$this->value};

    if (is_object($value) and $value->id)
      $html = html::em('a', array(
        'href' => 'admin/node/' . $value->id,
        ), html::cdata($value->getName()));
    else
      $html = t('Авторство не установлено.');

    return html::wrap('value', html::cdata($html));
  }

  /**
   * Запрещает редактировать это поле.
   */
  public function isEditable()
  {
    return false;
  }
}
