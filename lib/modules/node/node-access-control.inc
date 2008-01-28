<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AccessControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Таблица для работы с правами'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = "<tr><th>&nbsp;</th><th>C</th><th>R</th><th>U</th><th>D</th></tr>";

    foreach ($this->options as $k => $v) {
      $output .= '<tr>';
      $output .= '<td>'. mcms_plain($v) .'</td>';

      foreach (array('c', 'r', 'u', 'd') as $key) {
        $output .= '<td>';

        $output .= self::makeHTML('input', array(
          'type' => 'checkbox',
          'name' => "{$this->value}[{$k}][]",
          'value' => $key,
          'checked' => empty($data[$this->value][$k][$key]) ? null : 'checked',
          ));

        $output .= '</td>';
      }

      $output .= '</tr>';
    }

    return $this->wrapHTML('<table class=\'highlight\'>'. $output .'</table>');
  }
};
