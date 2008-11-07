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
  public static function getInfo()
  {
    return array(
      'name' => t('Текст с форматированием'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML($data)
  {
    if (null !== ($content = $data->{$this->value}))
      $content = htmlspecialchars($content);

    if (mcms::ismodule('tinymce'))
      TinyMceModule::add_extras();

    $output = mcms::html('textarea', array(
      'id' => $this->id,
      'class' => 'form-text visualEditor',
      'name' => $this->value,
      ), $content);

    return $this->wrapHTML($output);
  }

  public function set($value, Node &$node)
  {
    $this->validate($value);

    $node->{$this->value} = $value;
  }
};
