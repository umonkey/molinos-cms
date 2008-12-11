<?php
/**
 * Контрол для группирования других контролов.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для группирования других контролов.
 *
 * Используется, в частности, для формирования вкладок.
 *
 * @package mod_base
 * @subpackage Controls
 */
class FieldSetControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Набор вкладок'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('label'));

    if (empty($this->label))
      $this->label = '?';
  }

  public function getHTML($data)
  {
    $content = self::getChildrenHTML($data);

    if (!empty($content)) {
      $output = html::em('legend', html::em('span', $this->label));
      $output .= $content;

      if (null === $this->tabable or $this->tabable)
        $this->addClass('tabable');

      return html::em('fieldset', array(
        'class' => $this->class,
        ), $output);
    }
  }

  protected function getChildrenHTML($node = null)
  {
    $output = '';

    if (null != $this->intro)
      $output .= '<div class=\'intro\'>'. $this->intro .'</div>';

    return $output . parent::getChildrenHTML($node);
  }
};
