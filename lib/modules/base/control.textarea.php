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

  public function getHTML(array $data)
  {
    if (null === $this->value or empty($data[$this->value]))
      $content = null;
    else {
      if (is_array($content = $data[$this->value]))
        $content = join("\n", $content);
    }

    if (empty($this->content) and isset($this->default))
      $content = $this->default;

    $output = mcms::html('textarea', array(
      'id' => $this->id,
      'class' => array_merge($this->class, array('form-text', 'resizable')),
      'name' => $this->value,
      'rows' => $this->rows,
      'cols' => $this->cols,
      ), $content);

    return $this->wrapHTML($output);
  }
};
