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
      if (!array_key_exists('type', $value))
        throw new InvalidArgumentException(t('Не указан тип контрола.'));

      if (!class_exists($class = $value['type']))
        return null;

      return new $class($value + array('value' => $key));
    }

    throw new InvalidArgumentException(t('Содержимое схемы должно быть описано массивами или объектами-наследниками класса Control.'));
  }

  /**
   * Возвращает схему указанного типа документа.
   */
  public static function load(PDO_Singleton $db, $className)
  {
    $cache = cache::getInstance();
    $ckey = 'schema:' . $className;

    if (!is_array($fields = $cache->$ckey))
      $cache->$ckey = $fields = self::rebuild($db, $className);
    return new Schema($fields);
  }

  /**
   * Воссоздаёт контролы из БД.
   */
  public static function rebuild(PDO_Singleton $db, $className)
  {
    try {
      $node = Node::load(array(
        'class' => 'type',
        'name' => $className,
        'deleted' => 0,
        ), $db);
      $schema = (array)$node->fields;
    } catch (ObjectNotFoundException $e) {
      $schema = array();
    }

    return $schema;
  }

  /**
   * Удаляет из кэша указанный тип документа.
   */
  public static function flush($className)
  {
    unset(cache::getInstance()->{'schema:' . $className});
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
      if ($v->indexed and !in_array($k, array('name', 'uid', 'created', 'updated')) and null !== $v->getSQL())
        $result[] = $k;
    return $result;
  }

  /**
   * Формирует элементы формы с разбивкой на вкладки.
   */
  public function getForm(array $defaults = array(), $fieldName = null)
  {
    $tabs = array();

    // Сортировка
    if (null !== $fieldName)
      uasort($this, array($this, 'sortByWeight'));

    foreach ($this as $name => $ctl) {
      if (null !== $fieldName and $name != $fieldName)
        continue;

      if (!($group = trim($ctl->group)))
        $group = count($tabs)
          ? array_shift(array_keys($tabs))
          : t('Основные сведения');

      if ($ctl instanceof AttachmentControl)
        $group = t('Файлы');

      $ctl->value = $name;

      $tabs[$group][$name] = $ctl;
    }

    $form = new Form($defaults);

    // Несколько вкладок — филдсеты.
    if (count($tabs) > 1) {
      $form->addClass('tabbed');

      foreach ($tabs as $name => $controls) {
        $tab = $form->addControl(new FieldSetControl(array(
          'name' => $name,
          'label' => $name,
          'tab' => true,
          )));

        foreach ($controls as $control)
          $tab->addControl($control);
      }
    }

    // Всего одна вкладка — без филдсета.
    elseif (count($tabs) == 1) {
      foreach (array_shift($tabs) as $control)
        $form->addControl($control);
    }

    return $form;
  }

  private function sortByWeight($a, $b)
  {
    if (null === ($aweight = $a->weight))
      $aweight = 50;
    if (null === ($bweight = $b->weight))
      $bweight = 50;

    if (0 === ($delta = $aweight - $bweight))
      $delta = strcasecmp($a->value, $b->value);

    return $delta;
  }

  /**
   * Валидирует форму, возвращает данные.
   */
  public function getFormData(Context $ctx, &$data = null)
  {
    if (null === $data)
      $data = Control::data(array());

    foreach ($this as $name => $field) {
      $value = $ctx->post($name);
      $field->set($value, $data, $ctx->post);
    }

    return $data;
  }

  public function getXML()
  {
    $result = '';

    foreach ($this as $k => $v)
      $result .= html::em('field', array('name' => $k) + $v->dump());

    return html::wrap('schema', $result);
  }
}
