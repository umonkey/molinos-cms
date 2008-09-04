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

  public function getHTML(array $data)
  {
    if (empty($data[$this->value]))
      return;

    $rows = array();

    foreach ($data[$this->value] as $rid => $info) {
      if (empty($info['username']))
        $userlink = 'anonymous';
      else
        $userlink = l("?q=admin&mode=edit&cgroupo=access'
          .'&id={$info['uid']}&destination=CURRENT",
          $info['username']);

      $row = mcms::html('td', $rid);
      $row .= mcms::html('td', $info['created']);
      $row .= mcms::html('td', $userlink);
      $row .= mcms::html('td', l("?q=nodeapi.rpc&action=revert"
        ."&rid={$rid}&destination=CURRENT", 'вернуть'));

      $rows[] = mcms::html('tr', array(
        'class' => 'rev-'. ($info['active'] ? 'active' : 'passive'),
        ), $row);
    }

    return $this->wrapHTML(mcms::html('table', array(
      'class' => 'form-revision-history',
      ), join('', $rows)));
  }
};
