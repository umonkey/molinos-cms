<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class NodeLinkControl extends Control
{
  const limit = 50;

  public static function getInfo()
  {
    return array(
      'name' => t('Связь с документом'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'int(10) unsigned';
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (null !== ($output = $this->getSelect($this->getCurrentValue($data, true))))
      return $this->wrapHTML($output);

    $value = $this->getCurrentValue($data);

    $this->addClass('form-text');

    if (!$this->readonly)
      $this->addClass('autocomplete');

    $output = mcms::html('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => $this->class,
      'autocomplete' => 'off',
      'name' => $this->value,
      'value' => $value,
      'readonly' => $this->readonly ? 'readonly' : null,
      ));

    if (!$this->readonly) {
      $output .= '<script language=\'javascript\' type=\'text/javascript\'>$(function(){$(\'#'. $this->id .'\').suggest(\'/autocomplete.rpc?source='. $this->values .'\');});</script>';
      $output .= mcms::html('input', array(
        'type' => 'hidden',
        'name' => "nodelink_remap[{$this->value}]",
        'value' => $this->values . ($this->required ? '!' : ''),
        ));
    }

    return $this->wrapHTML($output);
  }

  private function getSelect($value)
  {
    if (count($parts = explode('.', $this->values, 2)) == 2) {
      if (Node::count($filter = array('class' => $parts[0], 'published' => 1, '#sort' => array('name' => 'asc'))) < self::limit) {
        $options = '';

        if (!$this->required)
          $options .= '<option></option>';

        foreach (Node::find($filter) as $tmp) {
          $name = $tmp->name;

          // FIXME: поправить интранет, заменить на fullname.
          if ($tmp->class == 'user' and isset($tmp->login))
            $name = $tmp->login;

          $options .= mcms::html('option', array(
            'selected' => ($value == $tmp->id) ? 'selected' : null,
            'value' => $tmp->id,
            ), $name);
        }

        return mcms::html('select', array(
          'name' => $this->value,
          ), $options);
      }
    }
  }

  private function getCurrentValue(array $data, $id = false)
  {
    if (isset($this->value) and !empty($data[$this->value])) {
      if (count($parts = explode('.', $this->values)) == 2) {
        if (count($nodes = array_values(Node::find(array('class' => $parts[0], 'id' => $data[$this->value]), 1))) == 1)
          return $id
            ? $nodes[0]->id
            : $nodes[0]->$parts[1];
      }
    }
  }
};
