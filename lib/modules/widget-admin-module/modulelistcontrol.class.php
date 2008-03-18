<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleListControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список модулей'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array());
  }

  public function getHTML(array $data)
  {
    if (!empty($data[$this->value][$this->group])) {
      $list = array();
      $rows = array();

      foreach ($data[$this->value][$this->group] as $k => $v) {
        $checked = !empty($v['enabled']);
        $disabled = ($this->group == 'Core');

        $row = '<td>'. mcms::html('input', array(
          'type' => 'checkbox',
          'value' => 1,
          'name' => $this->value .'['. $k .']',
          'checked' => $checked ? 'checked' : null,
          'disabled' => $disabled ? 'disabled' : null,
          )) .'</td>';
        $row .= '<td>'. mcms::html('a', array(
          'href' => 'http://code.google.com/p/molinos-cms/wiki/mod_'. str_replace('-', '_', $k),
          'target' => '_blank',
          'style' => 'white-space: nowrap',
          ), $k) .'</td>';
        $row .= '<td>'. mcms_plain($v['title']) .'</td>';

        if (!empty($v['config']))
          $row .= '<td>'. mcms::html('a', array(
            'href' => '/admin/builder/modules/?BebopModules.edit='. $k .'&destination='. urlencode($_SERVER['REQUEST_URI']),
            ), t('настроить')) .'</td>';
        else
          $row .= '<td>&nbsp;</td>';

        $rows[] = '<tr>'. $row .'</tr>';
      }

      if  (!empty($rows)) {
        $output = '<table class=\'highlight\'>';
        $output .= '<tr><th>&nbsp;</th><th>Имя</th><th>Описание</th><th>Действия</th></tr>';
        $output .= join('', $rows);
        $output .= '</table>';

        return $this->wrapHTML($output);
      }
    }
  }
};
