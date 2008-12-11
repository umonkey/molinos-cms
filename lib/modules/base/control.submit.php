<?php
/**
 * Контрол для сохранения формы.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для сохранения формы.
 *
 * Выводится в виде обычной кнопки submit.
 *
 * @package mod_base
 * @subpackage Controls
 */
class SubmitControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Кнопка отправки формы'),
      'hidden' => true,
      );
  }

  public function getHTML($data)
  {
    $output = $this->wrapHTML(html::em('input', array(
      'type' => 'submit',
      'id' => $this->id,
      'class' => array('form-submit'),
      'name' => $this->name,
      'value' => null !== $this->text ? $this->text : t('Сохранить'),
      'title' => $this->title,
      )), false);

    return $output;
  }
};
