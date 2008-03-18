<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CartControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Управление корзиной'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    if (empty($data[$this->value]))
      return null;

    $output = "<table>";
    $output .= "<tr class='header'>";
    $output .= "<th class='check'>&nbsp;</th>";
    $output .= "<th class='name'>". t('Название') ."</th>";
    $output .= "<th class='qty'>". t('Количество') ."</th>";
    $output .= "<th class='price'>". t('Цена') ."</th>";
    $output .= "<th class='sum'>". t('Сумма') ."</th>";
    $output .= "</tr>";

    $total = 0;

    foreach ($data[$this->value] as $row) {
      $output .= "<tr class='product'>";

      $output .= "<td class='check'>". mcms::html('input', array(
        'type' => 'checkbox',
        'name' => $this->value .'_checked[]',
        'value' => $row['id'],
        )) ."</td>";

      $output .= "<td class='name'>". mcms_plain($row['name']) ."</td>";

      $output .= "<td class='qty'>". mcms::html('input', array(
        'type' => 'text',
        'name' => "{$this->value}[{$row['id']}][qty]",
        'value' => $row['qty'],
        )) ."</td>";

      $output .= "<td class='price'>". number_format($row['price'], 2) ."</td>";
      $output .= "<td class='sum'>". number_format($row['sum'], 2) ."</td>";

      $output .= "</tr>";

      $total += $row['sum'];
    }

    $output .= "<tr class='total'>";
    $output .= "<td>&nbsp;</td>";
    $output .= "<td class='total'><strong>". t('Итого') .":</td>";
    $output .= "<td class='qty'>&nbsp;</td>";
    $output .= "<td class='price'>&nbsp;</td>";
    $output .= "<td class='sum'>". number_format($total, 2) ."</td>";
    $output .= "</tr>";

    $output .= "</table>";

    return $output;
  }
};
