<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FileListControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список прикреплённых файлов'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = "<tr><th>&nbsp;</th><th>Название файла</th><th>". mcms::html('img', array(
      'src' => 'themes/admin/img/bin.gif',
      'alt' => 'Убрать',
      )) ."</th></tr>";

    foreach ($data as $k => $v) {
      if ($this->value == substr($k, 0, strlen($this->value)) and is_numeric(substr($k, strlen($this->value) + 1, -1))) {
        $row = '<td>'. mcms::html('a', array(
          'href' => 'attachment/'. $v['id'],
          ), mcms::html('img', array(
          'alt' => $v['filename'],
          'src' => 'attachment/'. $v['id'] .',48,48,cw',
          'width' => 48,
          'height' => 48,
          ))) .'</td>';
        $row .= mcms::html('td', null, mcms::html('input', array(
          'type' => 'text',
          'class' => 'form-text',
          'value' => $v['name'],
          'name' => $k .'[name]',
          )));
        $row .= mcms::html('td', null, mcms::html('input', array(
          'type' => 'checkbox',
          'name' => $k .'[unlink]',
          'value' => 1,
          )));
        $output .= '<tr>'. $row .'</tr>';
      }
    }

    return $this->wrapHTML('<table>'. $output .'</table>');
  }
};
