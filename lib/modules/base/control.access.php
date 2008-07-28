<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:
//
// Входные параметры:
//   value   = имя массива с данными.
//   options = соответствие внутренних ключей отображаемым,
//             например: "Content Managers" => "Менеджеры контента",
//             ключи используются для формирования имён чекбоксов.
//
// Формат входного массива данных:
//   Ключ => (c => ?, r => ?, u => ?, d => ?, p => ?),
// Ключи соответствуют ключам параметра options.

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
    parent::__construct($form, array('value', 'options'));
  }

  public function getHTML(array $data)
  {
    $output = "<label>{$this->label}:</label><tr><th>&nbsp;</th><th>C</th><th>R</th><th>U</th><th>D</th><th>P</th></tr>";

    foreach ($this->options as $k => $v) {
      $output .= '<tr>';
      $output .= '<td>'. mcms_plain($v) .'</td>';

      foreach (array('c', 'r', 'u', 'd', 'p') as $key) {
        $output .= '<td>';

        $output .= mcms::html('input', array(
          'type' => 'checkbox',
          'name' => "{$this->value}[{$k}][]",
          'value' => $key,
          'checked' => empty($data[$this->value][$k][$key]) ? null : 'checked',
          ));

        $output .= '</td>';
      }

      $output .= '</tr>';
    }

    return $this->wrapHTML('<table class=\'padded highlight\'>'
      . $output .'</table>', false);
  }
};
