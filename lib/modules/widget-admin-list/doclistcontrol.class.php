<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class DocListControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список документов (таблица)'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'columns'));
  }

  public function getHTML(array $data)
  {
    $output = '';

    if (null !== $this->picker)
      $output .= '<script language=\'javascript\' type=\'text/javascript\'>var mcms_picker_id = \''. $this->picker .'\';</script>';

    $output .= $this->getHeaderHTML($this->sort);
    $output .= $this->getListHTML($data[$this->value]);

    return $this->wrapHTML(mcms::html('table', array('id' => $this->id, 'class' => 'highlight'), $output));
  }

  private function getHeaderHTML(array $sort = null)
  {
    $output = mcms::html('th', array(), '&nbsp;');

    foreach ($this->columns as $key => $column) {
      if (null !== $this->widget and null !== $this->sortable and in_array($key, $this->sortable)) {
        $url = bebop_split_url();

        if (null === $sort or empty($sort[$key]) or 'asc' != strtolower($sort[$key]))
          $dir = 'asc';
        else
          $dir = 'desc';

        $url['args'][$this->widget]['sortmode'] = $dir;
        $url['args'][$this->widget]['sort'] = $key;

        $text = l($column, $url['args']);

        if (null !== $sort and !empty($sort[$key])) {
          $text .= '&nbsp;';

          if (strtolower($sort[$key]) == 'desc')
            $text .= '↑';
          else
            $text .= '↓';
        }
      } else {
        $text = $column;
      }

      $output .= mcms::html('th', array(), $text);
    }

    return mcms::html('tr', array(), $output);
  }

  private function getListHTML(array $documents)
  {
    $output = '';

    foreach ($documents as $nid => $doc) {
      if (empty($doc['internal']))
        $check = mcms::html('input', array(
          'type' => 'checkbox',
          'value' => $nid,
          'name' => 'document_list_selected[]',
          ));
      else
        $check = '&nbsp;';

      $row = mcms::html('td', array(), $check);

      foreach ($this->columns as $col => $title) {
        $row .= mcms::html('td', array(), empty($doc[$col]) ? '&nbsp;' : $doc[$col]);
      }

      $class = array(empty($doc['published']) ? 'unpublished' : 'published');

      if (empty($doc['internal']))
        $class[] = 'data';

      if (null !== $this->picker)
        $class[] = 'return'. ucfirst($this->picker);

      $output .= mcms::html('tr', array('class' => $class), $row);
    }

    return $output;
  }
};
