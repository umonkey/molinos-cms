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

  public function getHTML($data)
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

      $row = html::em('td', $rid);
      $row .= html::em('td', $info['created']);
      $row .= html::em('td', $userlink);
      $row .= html::em('td', l("?q=nodeapi.rpc&action=revert"
        ."&rid={$rid}&destination=CURRENT", 'вернуть'));

      $rows[] = html::em('tr', array(
        'class' => 'rev-'. ($info['active'] ? 'active' : 'passive'),
        ), $row);
    }

    return $this->wrapHTML(html::em('table', array(
      'class' => 'form-revision-history',
      ), join('', $rows)));
  }
};
