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

  public function getExtraXMLContent()
  {
    $result = '';
    foreach (self::split($this->answers) as $k => $v)
      $result .= html::em('answer', array(
        'value' => $k,
        ), html::cdata($v));

    return parent::getExtraXMLContent() . html::wrap('answers', $result);
  }

  public function getPreviewXML(Context $ctx)
  {
    $xml = parent::getPreviewXML($ctx);
    $data = $ctx->db->getResultsKV("option", "count", "SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = ? GROUP BY `option`", array($this->id));

    $result = '';
    foreach (self::split($this->answers) as $k => $v) {
      $count = isset($data[$k])
        ? $data[$k]
        : 0;
      $result .= html::em('li', $v . ': ' . $count);
    }

    if (!empty($result)) {
      $value = html::em('value', html::cdata(html::em('ul', $result)));
      $xml .= html::em('field', array(
        'title' => t('Результаты'),
        'editurl' => "admin/edit/{$this->id}/answers?destination=" . urlencode(MCMS_REQUEST_URI),
        ), $value);
    }

    return $xml;
  }

  protected function getXMLStopFields()
  {
    $result = parent::getXMLStopFields();
    $result[] = 'answers';
    return $result;
  }

  public static function split($answers)
  {
    $result = array();

    foreach (explode("\n", $answers) as $line) {
      if (1 == count($parts = explode('=', $line, 2)))
        $parts[] = $parts[0];
      $result[trim($parts[0])] = trim($parts[1]);
    }

    return $result;
  }
};
