<?php
/**
 * Виджет «профиль пользователя».
 *
 * Используется для вывода и редактирования профиля пользователя.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «профиль пользователя».
 *
 * Используется для вывода и редактирования профиля пользователя.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class UserWidget extends Widget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array описание виджета, ключи: name, description.
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Профиль пользователя',
      'description' => 'Выводит форму авторизации, выхода, регистрации, восстановления пароля и редактирования профиля.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/UserWidget',
      );
  }

  /**
   * Препроцессор параметров.
   *
   * @param Context $ctx контекст запроса.
   *
   * @return array параметры виджета.
   */
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['uid'] = $this->get('uid');
    $options['#cache'] = false;

    return $options;
  }

  /**
   * Возвращает форму входа.
   */
  public function onGet(array $options)
  {
    if (empty($options['uid']))
      return $ctx->registry->unicast('ru.molinos.cms.auth.form', array($ctx));
  }
};
