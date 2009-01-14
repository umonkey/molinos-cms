<?php
/**
 * Контрол для управления историей правок.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для управления историей правок.
 *
 * Работает в пассивном режиме (в обработке формы не участвует).  Выводит
 * таблицу с ревизиями документа и ссылками (ведущими на RPC) для восстановления
 * архивных ревизий.
 *
 * @package mod_base
 * @subpackage Controls
 */
class HistoryControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список ревизий документа'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form);
  }

  public function getXML($data)
  {
    if (empty($data[$this->value]))
      return;

    $items = '';

    foreach ($data->{$this->value} as $rid => $info) {
      $items .= html::em('item', array(
        'uid' => $info['uid'],
        'username' => $info['username'],
        'rid' => $rid,
        'created' => $info['created'],
        ));
    }

    return empty($items)
      ? null
      : parent::wrapXML(array(), $items);
  }
};
