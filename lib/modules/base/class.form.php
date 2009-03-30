<?php
/**
 * Корневой элемент формы, частный случай «контрола».
 *
 * @see Control
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Корневой элемент формы, частный случай «контрола».
 *
 * @see Control
 *
 * @package mod_base
 * @subpackage Controls
 */
class Form extends Control
{
  /**
   * Возвращает информацию о контроле.
   *
   * @return array описание контрола, ключи: name, hidden (не разрешаем
   * создавать поля типа «форма»).
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Форма'),
      'hidden' => true,
      );
  }

  public function getXML($data)
  {
    return html::em('form', array(
      'method' => isset($this->method) ? $this->method : 'post',
      'action' => $this->getAction(),
      'class' => $this->class,
      'enctype' => 'multipart/form-data',
      'title' => $this->title,
      'intro' => $this->intro,
      ), parent::getChildrenXML($data));
  }

  private function getAction()
  {
    $action = isset($this->action) ? $this->action : MCMS_REQUEST_URI;

    if ($this->edit) {
      $url = new url($action);

      if (null !== ($next = $url->arg('destination'))) {
        $next = new url($next);
        $next->setarg('pending', null);
        $next->setarg('created', null);
        $destination = $next->string();

        $next->setarg('mode', 'edit');
        $next->setarg('type', null);
        $next->setarg('id', '%ID');
        $next->setarg('destination', $destination);

        $url->setarg('destination', $next->string());

        $action = $url->string();
      }
    }

    return $action;
  }
};
