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
    return array(
      'name' => t('Текстовое сообщение'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public function getHTML($data)
  {
    $text = $this->text;

    if (isset($this->url))
      $text .= '<p>'. mcms::html('a', array(
        'href' => $this->url,
        ), t('Подробная справка')) .'</p>';

    return isset($this->text)
      ? mcms::html('div', array('class' => 'intro'), $text)
      : null;
  }
};
