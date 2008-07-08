<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class NodeQueryBuilder
{
  private $query;

  // Сбрасываются при каждом запросе.
  private $pidx;
  private $tables;
  private $where;
  private $params;
  private $order;
  private $search;

  public function __construct(array $query)
  {
    if (!array_key_exists('deleted', $query))
      $query['deleted'] = 0;

    if (!empty($query['tags']) and is_array($query['tags']))
      $query['tags'] = array_unique($query['tags']);

    $this->query = $query;

    // Базовая оптимизация, актуальна для всех запросов.
    // $this->shrinkClasses();

    // Если выборка по коду ревизии отсутствует -- выбираем текущую ревизию.
    if (!array_key_exists('rid', $this->query))
      $this->where[] = "`node`.`rid` = `node__rev`.`rid`";
  }

  // Возвращает запрос для получения количества элементов.
  public function getCountQuery(&$sql, array &$params)
  {
    $this->scanBase($sql, $params);

    $sql = 'SELECT COUNT(*) '. $this->getFromPart();
    $params = $this->params;
  }

  public function getSelectQuery(&$sql, array &$params, array $fields = null)
  {
    $this->scanBase($sql, $params);
    $this->addSortFields();

    // Если мы работаем с одним классом, добавляем его специальные поля в запрос сразу.
    /*
    if (null !== $fields and null !== ($class = $this->getClassName())) {
      $schema = TypeNode::getSchema($class);
      $reserved = TypeNode::getReservedNames();

      if (!empty($schema['fields'])) {
        foreach ($schema['fields'] as $k => $v)
          if (!empty($v['indexed']) and !in_array($k, $reserved)) {
            $this->addTable('node_'. $class);
            $fields[] = "`node_{$class}`.`{$k}`";
          }
      }
    }
    */

    if ($fields === null)
      $fields = '*';
    else
      $fields = join(', ', $fields);

    $sql = 'SELECT '. $fields .' '. $this->getFromPart();
    $sql .= ' '. $this->getOrderPart();

    $params = $this->params;

    if (!empty($this->query['#debug']))
      mcms::debug($this, $sql, $params);
  }

  // Оставляет только один класс, если их много, и если в условии есть
  // выборка по расширенному свойству объекта, подразумевающая конкретный
  // класс.
  private function shrinkClasses()
  {
    if (empty($this->query['class']))
      return;

    if (!is_array($this->query['class']))
      $this->query['class'] = array($this->query['class']);

    foreach ($this->query as $k => $v) {
      // Нашли обращение к расширенному свойству.
      if (count($parts = explode('.', $k, 2)) != 2) {
        // Обращение к базовой таблице -- пропускаем.
        if ($parts[0] == 'node')
          continue;

        // Обращение к недоступному классу -- ошибка.
        if (!empty($this->query['class']) and is_array($this->query['class']) and !in_array($parts[0], $this->query['class']))
          throw new InvalidArgumentException("Попытка фильтрации по свойству {$table}.{$k} в то время, как выборка ограничена следующими классами: ". join(', ', $this->query['class']));

        // Ограничиваем выборку этим классом и выходим.
        $this->query['class'] = $parts[0];
        return;
      }
    }
  }

  // Сброс внутренних параметров, для возможности повторного использования.
  private function reset()
  {
    $this->pidx = 1;
    $this->tables = array('node', 'node__rev');
    $this->where = array("`node`.`lang` = 'ru'");
    $this->params = array();
    $this->order = array();
    $this->search = empty($this->query['#search']) ? null : $this->query['#search'];
  }

  // Разберает основную часть запроса.
  private function scanBase(&$sql, array &$params)
  {
    $this->reset();

    // Добавляем проверку прав.
    $this->addPermissionCheck();

    // Если нет выборки по rid -- используем текущую ревизию.
    if (empty($this->query['rid']))
      $this->where[] = "`node__rev`.`rid` = `node`.`rid`";
    else
      $this->where[] = "`node__rev`.`nid` = `node`.`id`";

    $this->scanSpecialSearch();

    foreach ($this->query as $k => $value) {
      // Всякий спецмусор обработаем потом, отдельно.
      if (substr($k, 0, 1) == '#')
        continue;

      // Разбиваем имя параметра на имя таблицы и имя поля.
      $parts = explode('.', $k, 2);

      // Обращение к конкретной таблице.
      if (count($parts) == 2) {
        $table = $parts[0];
        $field = $parts[1];
      }

      // Обращение к базовой таблице.
      else {
        $table = 'node';
        $field = $parts[0];
      }

      if (TypeNode::isReservedFieldName($field))
        $table = 'node';

      // Некоторые условия обрабатываются автоматически, например -- фильтрация по тэгам.
      if (null === ($mask = $this->getMask($table, $field)))
        continue;

      // Получаем полную спецификацию поля, вида `table`.`field`.
      if ($negate = ('-' == substr($mask, 0, 1)))
        $mask = substr($mask, 1);

      // Добавляем условие по этому полю.
      $this->addWhere($mask, $value, $negate);
    }

    // Контекстный поиск -- неотъемлимая часть.
    $this->scanSearch();

    // Специальные запросы тоже обрабатываются всегда.
    $this->addSpecialQueries();
  }

  // Разберает специальный поиск, типа uid:123.
  private function scanSpecialSearch()
  {
    if (null !== $this->search) {
      $parts = explode(' ', $this->search);

      foreach ($parts as $k => $v) {
        if (preg_match('/^([^:]+)\:([a-z0-9_,]+)$/', $v, $m)) {
          $this->query[$m[1]] = explode(',', $m[2]);
          unset($parts[$k]);
        }
      }

      $this->search = join(' ', $parts);
    }
  }

  // Разберает контекстный поиск.
  private function scanSearch()
  {
    if (empty($this->search))
      return;

    $matches = array();
    $needle = '%'. $this->search .'%';

    // Добавляем поиск по имени.
    $param = $this->getNextParam();
    $matches[] = "`node__rev`.`name` LIKE {$param}";
    $this->params[$param] = $needle;

    // Добавляем поиск по всем текстовым индексированным полям задействованных классов.
    if (!empty($this->query['class'])) {
      foreach ((array)$this->query['class'] as $class) {
        $schema = TypeNode::getSchema($class);

        foreach ($schema['fields'] as $field => $meta) {
          if (empty($meta['indexed']) or $field == 'name')
            continue;

          if (!in_array($meta['type'], array('TextLineControl', 'EmailControl')))
            continue;

          $this->addTable('node__idx_'. $class);

          $param = $this->getNextParam();

          $matches[] = "`node__idx_{$class}`.`{$field}` LIKE {$param}";
          $this->params[$param] = $needle;
        }
      }
    }

    $this->where[] = '('. join(' OR ', $matches) .')';
  }

  // Возвращает основную часть запроса: FROM ... WHERE ...
  private function getFromPart()
  {
    return 'FROM `'. join('`, `', $this->tables) .'` WHERE '. join(' AND ', $this->where);
  }

  // Возвращает сортировку: ORDER BY ...
  private function getOrderPart()
  {
    return empty($this->order) ? '' : 'ORDER BY '. join(', ', $this->order);
  }

  // Добавляет условие для конкретного поля.  Обрабатывает простые сравнения,
  // сравнения по маске и in.
  private function addWhere($mask, $value, $negate = false)
  {
    // Разворачиваем массив из одного элемента и обрабатываем поиск по ><
    if (is_array($value)) {
      if ($this->addRange($mask, $value))
        return;
      elseif (count($value) == 1)
        $value = array_pop($value);
    }

    // Нужны нулевые значения.
    if ($value === null) {
      if ($negate)
        $this->where[] = $mask .' IS NOT NULL';
      else
        $this->where[] = $mask .' IS NULL';
    }

    // Массив значений.
    elseif (is_array($value)) {
      // Выборка по диаппазону?
      if ($this->addRange($mask, $value))
        ;

      // Обычный список значений.
      else {
        $list = array();

        foreach ($value as $in) {
          $list[] = $param = $this->getNextParam();
          $this->params[$param] = $in;
        }

        $op = $negate ? 'NOT IN' : 'IN';

        $this->where[] = $mask .' '. $op .' ('. join(', ', $list) .')';
      }
    }

    // Строка для поиска или обычное сравнение.
    else {
      $param = $this->getNextParam();

      if ($negate)
        $op = strstr($value, '%') === false ? ' <> ' : ' NOT LIKE ';
      else
        $op = strstr($value, '%') === false ? ' = ' : ' LIKE ';

      $this->where[] = $mask . $op . $param;
      $this->params[$param] = $value;
    }
  }

  // Возвращает имя параметра.
  private function getNextParam()
  {
    return ':param'. $this->pidx++;
  }

  private function addRange($mask, $values)
  {
    $min = $max = null;

    foreach ($values as $value) {
      switch (substr($value, 0, 1)) {
      case '>':
        $min = substr($value, 1);
        break;
      case '<':
        $max = substr($value, 1);
        break;
      default:
        return false;
      }
    }

    $tmp = array();

    if ($min !== null) {
      $this->params[$param = $this->getNextParam()] = $min;
      $tmp[] = "{$mask} > {$param}";
    }

    if ($max !== null) {
      $this->params[$param = $this->getNextParam()] = $max;
      $tmp[] = "{$mask} < {$param}";
    }

    if (!empty($tmp))
      $this->where[] = '('. join(' AND ', $tmp) .')';

    return true;
  }

  // Формирует массив полей для сортировки.
  private function addSortFields()
  {
    if (!empty($this->query['#sort']) and is_string($this->query['#sort'])) {
      $fieldarr = preg_split("/\s+/", $this->query['#sort'], -1, PREG_SPLIT_NO_EMPTY);

      $this->query['#sort'] = array();
      foreach ($fieldarr as $f) {
        $dir = 'ASC';

        $sign = substr($f,0,1);
        if (($sign == '+') or ($sign == '-'))
          $f = substr($f, 1);

        if ($sign == '-')
          $dir = 'DESC';

        $this->query['#sort'][$f] = $dir;
      }
    }

    if (!empty($this->query['#sort']) and is_array($this->query['#sort'])) {
      foreach ($this->query['#sort'] as $field => $dir) {
        if ($field == 'name')
          $field = '`node__rev`.`name`';

        elseif ($field == 'RAND()')
          ;

        elseif (strstr($field, '.') === false)
          $field = "`node`.`{$field}`";

        else {
          $parts = explode('.', $field);
          $field = "`node__idx_{$parts[0]}`.`{$parts[1]}`";
          $this->addTable('node__idx_'. $parts[0]);
        }

        $this->order[] = "{$field} {$dir}";
      }
    }

    if (empty($this->order) and in_array('node__rel', $this->tables))
      $this->order[] = "`node__rel`.`order`";
  }

  // Добавляет специальные фильтры.
  private function addSpecialQueries()
  {
    if (!array_key_exists('#special', $this->query) or !is_string($this->query['#special']))
      return;

    switch ($this->query['#special']) {
    case 'orphan':
      $this->where[] = "`node`.`id` NOT IN (SELECT `nid` FROM `node__rel` WHERE `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'tag'))";
      $this->where[] = "`node`.`class` IN (SELECT `n`.`id` FROM `node` `n` INNER JOIN `node__idx_type` `t` ON `t`.`id` = `n`.`id` WHERE `n`.`class` = 'type' AND `t`.`hidden` = 0 AND `t`.`system` = 0)";
      break;

    case 'lost':
      $this->where[] = "`node`.`id` NOT IN (SELECT `nid` FROM `node__access` WHERE `r` = 1)";
      break;
    }
  }

  private function addPermissionCheck()
  {
    if (!empty($this->query['#permcheck']) and !mcms::config('bypass_permcheck')) {
      $filter = mcms::user()->getAccess('r');

      if (array_key_exists('class', $this->query))
        $tmp = (array)$this->query['class'];
      else
        $tmp = array();

      if (!empty($this->query['class']))
        $this->query['class'] = array_intersect($tmp, $filter);
      else
        $this->query['class'] = $filter;

      if (empty($this->query['class']))
        $this->query['class'] = null;
    }
  }

  // Добавляет выборку по разделам.
  private function addTagFilter()
  {
    if (!empty($this->query['tagged']))
      $this->where[] = "`node`.id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` IN (". join(', ', $this->query['tagged']) ."))";

    // Выборка по одному разделу -- используем обычную связку,
    // чтобы можно было отсортировать по ручному порядку.
    if (!is_array($this->query['tags']) or count($this->query['tags']) < 2) {
      if (!in_array('node__rel', $this->tables)) {
        $this->tables[] = 'node__rel';
        $this->where[] = "`node__rel`.`nid` = `node`.`id`";
      }

      return "`node__rel`.`tid`";
    }

    // Выборка по нескольким разделам.
    $tags = $this->query['tags'];

    foreach ($tags as $k => $v)
      if (!is_numeric($v))
        unset($tags[$k]);

    $this->where[] = "`node`.`id` IN (SELECT `nid` FROM `node__rel` WHERE `tid` IN (". join(', ', $tags) ."))";

    return null;
  }

  // Добавляет выборку по разделам.
  private function addTaggedFilter()
  {
    // Выборка по одному разделу -- используем обычную связку,
    // чтобы можно было отсортировать по ручному порядку.
    if (!is_array($this->query['tagged']) or count($this->query['tagged']) < 2) {
      if (!in_array('node__rel', $this->tables)) {
        $this->tables[] = 'node__rel';
        $this->where[] = "`node__rel`.`tid` = `node`.`id`";
      }

      return "`node__rel`.`nid`";
    }

    // Выборка по нескольким разделам.
    $tags = $this->query['tagged'];

    foreach ($tags as $k => $v)
      if (!is_numeric($v))
        unset($tags[$k]);

    $this->where[] = "`node`.`id` IN (SELECT `tid` FROM `node__rel` WHERE `nid` IN (". join(', ', $tags) ."))";

    return null;
  }

  // Возвращает спецификацию текущего поля.
  private function getMask($table, $field)
  {
    $mask = null;

    if (substr($table, 0, 1) == '-') {
      $negate = true;
      $table = substr($table, 1);
    } elseif (substr($field, 0, 1) == '-') {
      $negate = true;
      $field = substr($field, 1);
    } else {
      $negate = false;
    }

    if ($table == 'node') {
      switch ($field) {
      case 'id':
      case 'code':
      case 'class':
      case 'parent_id':
      case 'published':
      case 'deleted':
      case 'uid':
      case 'left':
      case 'right':
        $mask = "`node`.`{$field}`";
        break;

      case 'name':
      case 'html':
      case 'rid':
        $mask = "`node__rev`.`{$field}`";
        break;

      case 'tags':
        if (null === ($mask = $this->addTagFilter()))
          return $mask;
        break;

      case 'tagged':
        if (null === ($mask = $this->addTaggedFilter()))
          return $mask;
        break;

      case 'created.year':
        $mask = 'YEAR(`node`.`created`)';
        break;

      case 'created.month':
        $mask = 'MONTH(`node`.`created`)';
        break;

      case 'created.day':
        $mask = 'DAY(`node`.`created`)';
        break;

      default:
        // Мы сейчас работаем без указания таблицы.  Проверяем, можно ли
        // достать его из фильтра по классу, и, если нет -- валимся.
        if (array_key_exists('class', $this->query)) {
          $class = is_array($this->query['class']) ? $this->query['class'][0] : $this->query['class'];

          $schema = TypeNode::getSchema($class);

          if (!empty($schema['fields'][$field]['indexed'])) {
            $this->addTable('node__idx_'. $class);
            $mask = "`node__idx_{$class}`.`{$field}`";
          }
        }
      }
    }

    // Выборка по дополнительной таблице.
    else {
      $schema = TypeNode::getSchema($table);

      if (!empty($schema['fields'][$field]['indexed'])) {
        $this->addTable('node__idx_'. $table);
        $mask = "`node__idx_{$table}`.`{$field}`";
      }
    }

    if (null !== $mask)
      return ($negate ? '-' : '') . $mask;

    mcms::debug('No index: '. $field .', query follows.', $this->query);

    throw new NoIndexException($field);
  }

  // Добавляет таблицу в список участвующих в запросе.
  private function addTable($name)
  {
    if (!in_array($name, $this->tables)) {
      $this->tables[] = $name;
      $this->where[] = "`{$name}`.`id` = `node`.`id`";
    }
  }

  // Возвращает имя класса, если он один, или нулл.
  public function getClassName()
  {
    if (!empty($this->query['class']) and !is_array($this->query['class']))
      return $this->query['class'];
    return null;
  }
};
