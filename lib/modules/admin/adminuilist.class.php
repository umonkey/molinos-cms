<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUIList extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список объектов'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('columns'));
  }

  public function getHTML(array $data)
  {
    $output = '<table class=\'nodelist\' border=\'1\'>';
    $output .= $this->getTableHeader();

    $odd = true;

    foreach ($data['nodes'] as $node) {
      $classes = array();

      $classes[] = $odd ? 'odd' : 'even';
      $classes[] = $node->published ? 'published' : 'unpublished';

      if ($odd)
        $output .= '<tr class=\'odd\'>';
      else
        $output .= '<tr class=\'even\'>';

      $row = '<td>';
      $row .= mcms::html('input', array(
        'type' => 'checkbox',
        'name' => 'nodes[]',
        'value' => $node->id,
        ));
      $row .= '</td>';

      foreach ($this->columns as $field) {
        $row .= "<td class='field-{$field}'>";
        $value = $node->$field;

        if (empty($value))
          $row .= '&nbsp;';
        elseif ($field == 'name')
          $row .= mcms::html('a', array(
            'href' => '/admin/?mode=edit&id='. $node->id .'&destination='. urlencode($_SERVER['REQUEST_URI']),
            ), $node->$field);
        else
          $row .= $node->$field;

        $row .= '</td>';
      }

      $output .= mcms::html('tr', array(
        'class' => $classes,
        ), $row);

      $odd = !$odd;
    }

    $output .= '</table>';

    return $output;
  }

  private function getTableHeader()
  {
    $map = array(
      'name' => t('Название'),
      'title' => t('Заголовок'),
      'login' => t('Имя'),
      'email' => t('E-mail'),
      'created' => t('Дата создания'),
      'updated' => t('Дата изменения'),
      'filename' => t('Имя файла'),
      'filetype' => t('Тип содержимого'),
      'filesize' => t('Размер'),
      'class' => t('Тип'),
      'uid' => t('Автор'),
      );

    $output = '<tr>';
    $output .= '<th>&nbsp;</th>';

    foreach ($this->columns as $col) {
      $output .= "<th class='field-{$col}'>";

      if (array_key_exists($col, $map))
        $output .= $map[$col];
      else
        $output .= $col;

      $output .= '</th>';
    }

    return $output .'</tr>';
  }
};
