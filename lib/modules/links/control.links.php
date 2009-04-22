<?php

class LinksControl extends TextAreaControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Ссылки'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['description']))
      $form['description'] = 'Одна строка = одна ссылка. Каждая строка содержит URL и его описание, отделяются пробелами, например: «http://ru.wikipedia.org/wiki/xyz Мы в википедии».';
    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    $lines = array();
    foreach ((array)$data->{$this->value} as $line) {
      $lines[] = (count($line) > 1)
        ? $line['href'] . ' ' . $line['name']
        : $line['href'];
    }

    $this->addClass('form-text');
    $this->addClass('resizable');
    $this->addClass('nowrap');

    return parent::wrapXML(array(
      'rows' => $this->rows,
      'cols' => $this->cols,
      ), html::cdata(join("\n", $lines)));
  }

  public function set($value, &$node)
  {
    $result = array();

    foreach (preg_split('/[\r\n]+/', $value) as $line) {
      if (count($parts = preg_split('/\s+/', $line, 2, PREG_SPLIT_NO_EMPTY))) {
        $link = array();
        if ($tmp = array_shift($parts))
          $link['href'] = $tmp;
        if ($tmp = array_shift($parts))
          $link['name'] = $tmp;
        if (!empty($link['href']))
          $link['host'] = url::host($link['href']);

        try {
          $head = http::head($link['href']);

          if (200 == ($link['status'] = $head['_status'])) {
            if (!empty($head['Content-Type']))
              $link['type'] = $head['Content-Type'];
            if (!empty($head['Content-Length'])) {
              $link['size'] = intval($head['Content-Length']);
              $link['sizefm'] = mcms::filesize($link['size']);
            }
          }
        } catch (Exception $e) {
        }

        $result[] = $link;
      }
    }

    if (empty($result))
      unset($node->{$this->value});
    else
      $node->{$this->value} = $result;
  }

  public function format($value)
  {
    $tmp = '';
    foreach ((array)$value as $line)
      $tmp .= html::em('link', $line);
    return $tmp;
  }
}
