<?php
/**
 * Контрол для группирования других контролов.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для группирования других контролов.
 *
 * Используется, в частности, для формирования вкладок.
 *
 * @package mod_base
 * @subpackage Controls
 */
class FieldSetControl extends Control
{
  public static function getInfo()
  {
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('label'));

    if (empty($this->label))
      $this->label = '?';
  }

  public function getXML($data)
  {
    $content = self::getChildrenXML($data);

    if (!empty($content))
      return parent::wrapXML(array(
        'type' => 'group',
        'tab' => (bool)$this->tab,
        'mode' => $this->mode,
        ), $content);
  }
};
