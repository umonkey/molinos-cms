<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollNode extends Node implements iContentType
{
  public static function getDefaultSchema()
  {
    return array(
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим работы'),
        'required' => true,
        'options' => array(
          'single' => t('одно значение'),
          'multi' => t('несколько значений'),
          ),
        ),
      );
  }

  public function getOptions()
  {
    $options = array();

    foreach (preg_split('/[\r\n]+/', $this->answers) as $idx => $name)
      $options[$idx + 1] = $name;

    return $options;
  }

  public function getResults(Context $ctx)
  {
    $data = $ctx->db->getResultsKV("option", "count", "SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = ?", array($this->id));

    $total = 0;
    foreach ($data as $v)
      $total += intval($v);

    $result = array();
    foreach ($this->getOptions() as $k => $v)
      $result[$k] = array(
        'name' => $v,
        'count' => isset($data[$k])
          ? intval($data[$k])
          : 0,
        'percent' => intval($data[$k]) * 100 / $total,
        );

    return $result;
  }
};
