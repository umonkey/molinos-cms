<?php
/**
 * Контрол для вывода подсказок.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для вывода подсказок.
 *
 * Пассивный контрол (в обработке форм не участвует).  Используется для вывода
 * произвольного текста в произвольном месте формы.
 *
 * @package mod_base
 * @subpackage Controls
 */
class InfoControl extends Control
{
  public static function getInfo()
  {
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public function getXML($data)
  {
    return parent::wrapXML(array(
      'text' => $this->text,
      'url' => $this->url,
      ));
  }

  public function set($value, Node &$node)
  {
    // Ничего не делаем.
  }
};
