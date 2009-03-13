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
  }

  public function getXML($data)
  {
    $this->addClass('form-submit');

    return parent::wrapXML(array(
      'value' => null !== $this->text ? $this->text : t('Сохранить'),
      ));
  }
};
