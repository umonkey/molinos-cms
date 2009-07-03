<?php
/**
 * Контрол для ввода ссылки (URL).
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода ссылки (URL).
 *
 * @package mod_base
 * @subpackage Controls
 */
class URLControl extends EmailControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => 'Адрес страницы или сайта',
      );
  }

  public function getSQL()
  {
    return null;
  }

  public function format(Node $node, $em)
  {
    if ($value = $node->{$this->value}) {
      $result = html::em($em, array(
        'host' => url::host($value),
        ), html::cdata($value));
      return $result;
    }
  }

  public function preview($data)
  {
    if ($url = $data->{$this->value}) {
      $a = html::em('a', array(
        'href' => $url,
        ), html::plain($url));
      return html::em('value', array(
        'html' => true,
        ), html::cdata($a));
    }
  }
};
