<?php
/**
 * Контрол для вывода таблицы.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для вывода таблицы.
 *
 * Устаревший, очень сложный механизм.
 * @todo устранить.
 *
 * @package mod_base
 * @subpackage Controls
 */
class TableControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Таблица'),
      'hidden' => true,
      );
  }

  public function getHTML($data)
  {
    mcms::debug($this, $data);
  }
};
