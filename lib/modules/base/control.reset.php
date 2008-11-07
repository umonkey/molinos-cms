<?php
/**
 * Контрол для очистки формы.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для очистки формы.
 *
 * Выводит стандартную кнопку, возвращающую форму в исходное состояние.
 *
 * @package mod_base
 * @subpackage Controls
 */
class ResetControl extends Control
{
  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public static function getInfo()
  {
    return array(
      'name' => t('Кнопка очистки формы'),
      'hidden' => true,
      );
  }

  public function getHTML($data)
  {
    return mcms::html('input', array(
      'type' => 'reset',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->name,
      'value' => isset($this->text) ? $this->text : t('Очистить'),
      'title' => $this->title,
      ));
  }
};
