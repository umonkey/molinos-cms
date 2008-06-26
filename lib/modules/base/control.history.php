<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
        $userlink = l("admin?mode=edit&cgroupo=access'
          .'&id={$info['uid']}&destination=CURRENT",
          $info['username']);

      $row = mcms::html('td', $rid);
      $row .= mcms::html('td', $info['created']);
      $row .= mcms::html('td', $userlink);
      $row .= mcms::html('td', l("nodeapi.rpc?action=revert"
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
