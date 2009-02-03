<?php

class html
{
  /**
   * Renders an HTML element.
   *
   * Returns the HTML representation of an element described by
   * input parameters which are: element name, an array of attributes,
   * and the content.  Except for the first parameter, all is optional.
   *
   * @return string
   * @author Justin Forest
   */
  public static function em()
  {
    if (func_num_args() == 0 or func_num_args() > 3)
      throw new InvalidArgumentException(t('html::em() принимает от одного до трёх параметров.'));
    else {
      $args = func_get_args();
      $name = array_shift($args);

      if (empty($name))
        throw new InvalidArgumentException(t('Попытка создать HTML элемент без имени.'));

      $parts = null;
      $content = null;

      if (is_array($tmp = array_shift($args)))
        $parts = $tmp;
      else
        $content = $tmp;

      if (!empty($args))
        $content = array_shift($args);
    }

    $output = '<'. $name;

    if (('td' == $name or 'th' == $name) and empty($content))
      $content = '&nbsp;';

    if (empty($parts))
      $parts = array();

    $fixmap = array(
      'a' => 'href',
      'form' => 'action',
      );

    // Замена CURRENT на текущий адрес.
    if (array_key_exists($name, $fixmap)) {
      if (array_key_exists($fixmap[$name], $parts)) {
        $parts[$fixmap[$name]] = str_replace(array(
          '&destination=CURRENT',
          '?destination=CURRENT',
          ), array(
          '&destination='. urlencode($_SERVER['REQUEST_URI']),
          '?destination='. urlencode($_SERVER['REQUEST_URI']),
          ), strval($parts[$fixmap[$name]]));
      }
    }

    $output .= self::attrs($parts);

    if (null === $content and !in_array($name, array('a', 'script', 'div', 'textarea', 'span', 'base'))) {
      $output .= ' />';
    } else {
      $output .= '>'. $content .'</'. $name .'>';
    }

    return $output;
  }

  public static function attrs(array $attrs)
  {
    $result = '';

    foreach ($attrs as $k => $v) {
      if (is_array($v)) {
        if ('class' == $k)
          $v = join(' ', array_unique($v));
        else
          continue;
      }

      if (null === $v or '' === $v)
        continue;

      if (!empty($v)) {
        if (true === $v)
          $v = 'yes';
        else
          $v = mcms_plain($v, false);
        $result .= ' '.$k.'=\''. $v .'\'';
      } elseif ($k == 'value') {
        $result .= " value=''";
      }
    }

    return $result;
  }

  public static function simpleList(array $elements)
  {
    $result = '';

    foreach ($elements as $em)
      $result .= self::em('li', htmlspecialchars($em));

    return $result;
  }

  public static function simpleOptions(array $elements)
  {
    $output = '';

    foreach ($elements as $k => $v)
      $output .= html::em('option', array(
        'value' => $k,
        ), $v);

    return $output;
  }

  public static function cdata($data)
  {
    if (empty($data))
      return null;

    if (!is_string($data))
      throw new InvalidArgumentException(t('Параметр для html::cdata() должен быть строкой.'));

    if (strlen($data) != strcspn($data, '<>&'))
      return '<![CDATA[' . $data . ']]>';
    else
      return $data;
  }

  public static function formatExtras(array $extras)
  {
    $output = '';

    foreach ($extras as $item) {
      switch ($item[0]) {
      case 'style':
        $output .= html::em('link', array(
          'rel' => 'stylesheet',
          'type' => 'text/css',
          'href' => $item[1],
          ));
        break;
      case 'script':
        $output .= html::em('script', array(
          'type' => 'text/javascript',
          'src' => $item[1],
          ));
        break;
      }
    }

    return $output;
  }
}
