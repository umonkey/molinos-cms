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
    if (empty($form['values']) and !empty($form['dictionary']))
      $form['values'] = $form['dictionary'] .'.name';

    parent::__construct($form, array('value'));
  }

  public static function getSQL()
  {
    return 'int';
  }

  public function getHTML(array $data)
  {
    if (isset($this->hidden))
      return $this->getHidden($data);

    if (null !== ($output = $this->getSelect(strval($this->getCurrentValue($data)))))
      return $this->wrapHTML($output);

    if (($value = $this->getCurrentValue($data)) instanceof Node)
      $name = $value->name;
    elseif (is_numeric($value))
      $name = Node::load($value)->name;
    else
      $name = '(связь нарушена)';

    $this->addClass('form-text');

    if (!$this->readonly)
      $this->addClass('autocomplete');

    $output = mcms::html('input', array(
      'type' => 'text',
      'id' => $this->id,
      'class' => $this->class,
      'autocomplete' => 'off',
      'name' => $this->value,
      'value' => $name,
      'readonly' => $this->readonly ? 'readonly' : null,
      ));

    if (!$this->readonly) {
      $output .= '<script language=\'javascript\' type=\'text/javascript\'>$(function(){$(\'#'. $this->id .'\').suggest(\'autocomplete.rpc?source='. $this->values .'\');});</script>';
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
        foreach (Node::find($filter) as $tmp) {
          $name = $tmp->name;

          if ($tmp->class == 'user' and !empty($tmp->fullname))
            $name = $tmp->fullname;

          $values[$tmp->id] = $name;
        }

        asort($values);

        $options = '';

        if (!$this->required)
          $options .= '<option></option>';

        foreach ($values as $id => $name) {
          $options .= mcms::html('option', array(
            'selected' => ($value == $id) ? 'selected' : null,
            'value' => $id,
            ), $name);
        }

        return mcms::html('select', array(
          'name' => $this->value,
          ), $options);
      }
    }
  }

  // Возвращает текущее значение поля.
  private function getCurrentValue(array $data)
  {
    return array_key_exists($this->value, $data) ? $data[$this->value] : null;
  }
};
