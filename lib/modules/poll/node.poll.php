<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollNode extends Node implements iContentType
{
  public function getOptions($withCounts = true)
  {
    $total = 0;
    $result = array();

    foreach (preg_split('/[\r\n]+/', $this->answers) as $idx => $name) {
      $parts = explode('=', $name, 2);
      if (count($parts) == 1)
        array_unshift($parts, $idx);
      $result[$parts[0]] = array(
        'text' => $parts[1],
        'count' => 0,
        'percent' => 0,
        );
    }

    // Запрашиваем информацию о голосах.
    foreach ($this->getDB()->getResultsKV('option', 'count', 'SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = ? GROUP BY `option`', array($this->id)) as $k => $v) {
      if (array_key_exists($k, $result)) {
        $result[$k]['count'] = $v;
        $total += $v;
      }
    }

    // Считаем проценты.
    if ($total)
      foreach ($result as $k => $v)
        $result[$k]['percent'] = $v['count'] / ($total / 100);

    $result['#total'] = $total;

    return $result;
  }

  public function getOptionsXML()
  {
    $xml = '';
    $total = 0;

    foreach ($options = $this->getOptions() as $k => $v) {
      if ('#total' == $k)
        $total = $v;
      else
        $xml .= html::em('option', array(
          'value' => $k,
          'count' => $v['count'],
          'percent' => $v['percent'],
          ), $v['text']);
    }

    return html::em('options', array(
      'votes' => $total,
      ), $xml);
  }

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

  public function getExtraXMLContent()
  {
    $result = '';

    foreach (explode("\n", $this->answers) as $answer) {
      if (1 == count($parts = explode('=', $answer)))
        $parts[] = $parts[0];
      $result .= html::em('answer', array(
        'value' => trim($parts[0]),
        ), html::cdata(trim($parts[1])));
    }

    return parent::getExtraXMLContent() . html::wrap('answers', $result);
  }

  protected function getXMLStopFields()
  {
    $result = parent::getXMLStopFields();
    $result[] = 'answers';
    return $result;
  }
};
