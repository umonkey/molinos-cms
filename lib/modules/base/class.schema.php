<?php

class Schema extends ArrayObject
{
  public function __construct(array $fields = array())
  {
    foreach ($fields as $k => $v)
      if (null !== ($ctl = $this->rebuild($v, $k)))
        $fields[$k] = $ctl;
      else
        unset($fields[$k]);

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
        throw new InvalidArgumentException(t('Внешнее имя поля не соответствует внутреннему (%a vs %b).', array(
          '%a' => $value->value,
          '%b' => $key,
          )));
      else
        return $value;
    }

    elseif (is_array($value)) {
      if (!class_exists($class = $value['type']))
        return null;

      return new $class($value + array('value' => $key));
    }

    throw new InvalidArgumentException(t('Содержимое схемы должно быть описано массивами или объектами-наследниками класса Control.'));
  }

  public static function load($class, Node $subject = null)
  {
    $s = Structure::getInstance();

    if (false === ($schema = $s->findSchema($class))) {
      mcms::flog('schema', $class . ': loading from file, please update schema (just delete the file).');

      try {
        $node = Node::load(array(
          'class' => 'type',
          'deleted' => 0,
          'name' => $class,
          ));

        $schema = $node->fields;

        // Применяем дефолтные поля.
        if (null !== $subject) {
          $hasfields = count($schema);

          foreach ($subject->getDefaultSchema() as $k => $v) {
            if (!empty($v['recommended']) and $hasfields)
              continue;

            if (!isset($schema[$k]) or !empty($v['volatile']) and class_exists($v['type'])) {
              $class = $v['type'];
              unset($v['type']);
              $v['value'] = $k;

              $schema[$k] = new $class($v);
            }
          }
        }
      } catch (ObjectNotFoundException $e) {
        $schema = array();
      }
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
