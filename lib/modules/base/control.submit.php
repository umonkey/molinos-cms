<?php
/**
 * Контрол для сохранения формы.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для сохранения формы.
 *
 * Выводится в виде обычной кнопки submit.
 *
 * @package mod_base
 * @subpackage Controls
 */
class SubmitControl extends Control
{
  public function __construct(array $form)
  {
    parent::__construct($form, array('text'));
  }

  public static function getInfo()
  {
    return array(
      'name' => t('Кнопка отправки формы'),
      'hidden' => true,
      );
  }

  public function getHTML(array $data)
  {
    $output = '';

    if ($this->captcha and null !== ($cval = mcms::captchaGen())) {
      $key = mcms_encrypt($cval);

      $output .= mcms::html('img', array(
        'src' => 'captcha.rpc?seed='. $key,
        'alt' => 'captcha',
        ));
      $output .= '<div class="captchablock">';
      $output .= '<label for="captcha-'.$this->id.'">Введите текст с картинки:</label>';
      $output .= '<input id="captcha-'.$this->id.'" type="text" name="captcha[]" />';
      $output .= '<input type="hidden" name="captcha[]" value="'. $key .'" />';
      $output .= '</div>';
    }

    $output .= $this->wrapHTML(mcms::html('input', array(
      'type' => 'submit',
      'id' => $this->id,
      'class' => array('form-submit'),
      'name' => $this->name,
      'value' => null !== $this->text ? $this->text : t('Сохранить'),
      'title' => $this->title,
      )), false);

    return $output;
  }
};
