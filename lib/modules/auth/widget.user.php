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
  protected function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);
    $options['uid'] = $this->ctx->user->id; // $this->get('uid');
    $options['#cache'] = false;
    return $options;
  }

  /**
   * Возвращает форму входа.
   */
  public function onGet(array $options)
  {
    $result = '';
    if ($this->ctx->user->id)
      $result .= Node::findXML(array('id' => $this->ctx->user->getNode()->id));

    if (empty($options['uid']))
      return $result . $this->ctx->registry->unicast('ru.molinos.cms.auth.form', array($this->ctx));
    else {
      $node = $this->ctx->user->getNode();
      $form = $node->formGet()->getXML($node);
      return $result . $form;
    }
  }
};
