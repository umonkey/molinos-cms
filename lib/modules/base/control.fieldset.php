<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

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
  }

  public function getHTML(array $data)
  {
    $content = self::getChildrenHTML($data);

    if (!empty($content)) {
      $output = mcms::html('legend', mcms::html('span', $this->label));
      $output .= $content;

      $this->addClass('tabable');

      return mcms::html('fieldset', array(
        'class' => $this->class,
        ), $output);
    }
  }

  protected function getChildrenHTML(array $data)
  {
    $output = '';

    if (null != $this->intro)
      $output .= '<div class=\'intro\'>'. $this->intro .'</div>';

    return $output . parent::getChildrenHTML($data);
  }
};
