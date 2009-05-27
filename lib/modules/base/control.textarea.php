<?php
/**
 * Контрол для ввода неформатированного текста.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода неформатированного текста.
 *
 * @package mod_base
 * @subpackage Controls
 */
class TextAreaControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Текст без форматирования'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['rows']))
      $form['rows'] = 5;
    if (empty($form['cols']))
      $form['cols'] = 50;

    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    if (is_array($content = $data->{$this->value}))
      $content = join("\n", $content);

    if (empty($content) and isset($this->default))
      $content = $this->default;

    return parent::wrapXML(array(
      'type' => 'textarea',
      'rows' => $this->rows,
      'cols' => $this->cols,
      ), html::cdata($content));
  }

  public function set($value, &$node)
  {
    $this->validate($value = trim($value));

    $node->{$this->value} = $value;
  }

  /**
   * Форматирование значения. Вызывает обработчики вроде типографа.
   */
  public function format($value, $em)
  {
    $ctx = Context::last();
    $ctx->registry->broadcast('ru.molinos.cms.format.text', array($ctx, $this->value, &$value));
    return html::wrap($em, html::cdata($value));
  }
};
