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
class TextHTMLControl extends TextAreaControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Текст с форматированием'),
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
    return parent::wrapXML(array(
      'type' => 'textarea',
      'mode' => 'visual',
      'class' => 'visualEditor',
      'rows' => $this->rows,
      'cols' => $this->cols,
      ), html::cdata($data->{$this->value}));
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    $node->{$this->value} = $value;
  }

  /**
   * Форматирование значения. Вызывает обработчики вроде типографа.
   */
  public function format(Node $node, $em)
  {
    $value = $node->{$this->value};
    $ctx = Context::last();
    $ctx->registry->broadcast('ru.molinos.cms.format.text', array($ctx, $this->value, &$value));
    return html::wrap($em, html::cdata($value));
  }
};
