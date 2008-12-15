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

    if (null === $content and !in_array($name, array('a', 'script', 'div', 'textarea', 'span'))) {
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
      if (!empty($v)) {
        if (is_array($v))
          if ($k == 'class')
            $v = join(' ', $v);
          else {
            $v = null;
          }

        $result .= ' '.$k.'=\''. mcms_plain($v, false) .'\'';
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
}
