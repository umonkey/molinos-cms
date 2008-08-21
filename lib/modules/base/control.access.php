<?php
/**
 * Контрол для управления правами.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для управления правами.
 *
 * Входные параметры: value = имя массива с данными, options = соответствие
 * внутренних ключей отображаемым, например: "Content Managers" => "Менеджеры
 * контента", ключи используются для формирования имён чекбоксов.
 *
 * Формат входного массива данных: ключ => (c => ?, r => ?, u => ?, d => ?,
 * p => ?).  Ключи соответствуют ключам параметра options.
 *
 * @package mod_base
 * @subpackage Controls
 */
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
