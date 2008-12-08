<?php

class Schema extends ArrayObject
{
  public function __construct(array $fields = array())
  {
    foreach ($fields as $k => $v)
      if (null !== ($ctl = $this->rebuildControl($v, $k)))
        $fields[$k] = $ctl;
      else
        unset($fields[$k]);

    return parent::__construct($fields);
  }

  public function offsetSet($index, $value)
  {
    return parent::offsetSet($index, $this->rebuildControl($value, $index));
  }

  private function rebuildControl($value, $key = null)
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

  public static function load($class)
  {
    return new Schema(Structure::getInstance()->findSchema($class, array()));
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

  /**
   * Возвращает схему указанного типа документа.
   *
   * Читает сохранённую версию из БД, применяет
   * дефолтные поля при необходимости.
   */
  public static function rebuild($class)
  {
    // Загружаем из БД.
    try {
      $node = Node::load(array(
        'class' => 'type',
        'deleted' => 0,
        'name' => $class,
        ));

      $schema = $node->fields;
    } catch (ObjectNotFoundException $e) {
      $schema = array();
    }

    // Применяем дефолтные поля.
    if (null !== ($host = Node::getClassName($class))) {
      if (method_exists($host, 'getDefaultSchema')) {
        if (is_array($default = call_user_func(array($host, 'getDefaultSchema')))) {
          $hasfields = count($schema);

          foreach ($default as $k => $v) {
            if (!empty($v['recommended']) and $hasfields)
              ;

            elseif (!empty($v['deprecated'])) {
              if (isset($schema[$k]))
                unset($schema[$k]);
            }

            elseif (!isset($schema[$k]) or !empty($v['volatile']))
              $schema[$k] = $v;
          }
        }
      }
    }

    // Очистка от мусора.
    foreach ($schema as $field => $meta)
      foreach ($meta as $k => $v)
        if (empty($v))
          unset($schema[$field][$k]);

    return $schema;
  }
}
