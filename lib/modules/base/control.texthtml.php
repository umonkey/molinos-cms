<?php
/**
 * Контрол для ввода текста с форматированием.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для ввода текста с форматированием.
 *
 * На данный момент в качестве средства форматирования поддерживается только
 * TinyMCE.
 *
 * @package mod_base
 * @subpackage Controls
 */
class TextHTMLControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Текст с форматированием'),
      'class' => __CLASS__,
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['rows']))
      $form['rows'] = 10;
    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    if (null !== ($content = $data->{$this->value}))
      $content = htmlspecialchars($content);

    if (class_exists('TinyMceModule'))
      TinyMceModule::add_extras(Context::last());

    $this->addClass('form-text');
    $this->addClass('visualEditor');

    return parent::wrapXML(array(
      'rows' => $this->rows,
      'cols' => $this->cols,
      ), html::cdata($content));
  }

  public function set($value, Node &$node)
  {
    $this->validate($value);

    $node->{$this->value} = $value;
  }

  /**
   * Форматирование значения. Вызывает обработчики вроде типографа.
   */
  public function format($value)
  {
    $ctx = Context::last();
    $ctx->registry->broadcast('ru.molinos.cms.format.text', array($ctx, $this->value, &$value));
    return $value;
  }
};
