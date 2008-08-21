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

  /**
   * Формирование HTML-кода формы.
   *
   * Опционально добавляет заголовок и вступление (свойство intro), завёрнутое в
   * div class=intro.
   *
   * @return string HTML-код формы.
   */
  public function getHTML(array $data)
  {
    $output = '';

    if (isset($this->title)) {
      if (!in_array($header = $this->header, array('h2', 'h3', 'h4', 'h5')))
        $header = 'h2';
      $output = "<{$header}><span>". mcms_plain($this->title) ."</span></{$header}>";
    }

    if (null != $this->intro)
      $output .= '<div class=\'intro\'>'. $this->intro .'</div>';

    $output .= mcms::html('form', array(
      'method' => isset($this->method) ? $this->method : 'post',
      'action' => $this->getAction(),
      'id' => $this->id,
      'class' => $this->class,
      'enctype' => 'multipart/form-data',
      ), parent::getChildrenHTML($data));

    return $output;
  }

  private function getAction()
  {
    $action = isset($this->action) ? $this->action : $_SERVER['REQUEST_URI'];

    if ($this->edit) {
      $url = new url($action);

      if (null !== ($next = $url->arg('destination'))) {
        $next = new url($next);
        $next->setarg('pending', null);
        $next->setarg('created', null);
        $destination = strval($next);

        $next->setarg('mode', 'edit');
        $next->setarg('type', null);
        $next->setarg('id', '%ID');
        $next->setarg('destination', $destination);

        $url->setarg('destination', strval($next));

        $action = strval($url);
      }
    }

    return $action;
  }
};
