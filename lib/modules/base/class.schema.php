<?php

class Schema extends ArrayObject
{
  public function __construct(array $fields = array())
  {
    foreach ($fields as $k => $v)
      $fields[$k] = $this->rebuild($v, $k);

    return parent::__construct($fields);
  }

  public function offsetSet($index, $value)
  {
    return parent::offsetSet($index, $this->rebuild($value, $index));
  }

  private function rebuild($value, $key = null)
  {
    if ($value instanceof Control) {
      if (!empty($value->value) and $key != $value->value)
        throw new InvalidArgumentException(t('Внешнее имя поля не соответствует внутреннему.'));
      else
        return $value;
    }

    elseif (is_array($value)) {
      if (!class_exists($class = $value['type']))
        $class = 'Control';

      return new $class($value + array('value' => $key));
    }

    throw new InvalidArgumentException(t('Содержимое схемы должно быть описано массивами или объектами-наследниками класса Control.'));
  }

  public static function load($class)
  {
    $schema = mcms::cache($key = 'schema:fields:' . $class);

    if (!is_array($schema)) {
      $node = Node::load(array(
        'class' => 'type',
        'deleted' => 0,
        'name' => $class,
        ));

      mcms::cache($key, $schema = $node->fields);
    }

    return new Schema($schema);
  }

  public function hasIndex($name)
  {
    if (!isset($this[$name]))
      return false;
    return (bool)$this[$name]->indexed;
  }

  /**
   * Проверка наличия созданных вручную индексов.
   * Стандартные индексы (name, uid итд) не учитываются.
   */
  public function hasIndexes()
  {
    return count($this->getIndexes()) > 0;
  }

  /**
   * Возвращает имена полей, по которым созданы индексы.
   */
  public function getIndexes()
  {
    $result = array();
    foreach ($this as $k => $v)
      if ($v->indexed and !in_array($k, array('name', 'uid', 'created', 'updated')))
        $result[] = $k;
    return $result;
  }
}
