<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class Tagger
{
    const MOVE_UP = 1;
    const MOVE_DOWN = 2;

    private $pdo = null;

    private static $instance = null;

    // Инициализация объекта.
    public function __construct(PDO_Singleton $pdo = null)
    {
        $this->pdo = ($pdo === null) ? mcms::db() : $pdo;
    }

    public function flushCache()
    {
    }

    // Возвращает указатель на статический объект.
    public static function getInstance()
    {
        if (self::$instance === null)
            self::$instance = new Tagger();
        return self::$instance;
    }

    // Возвращает список дочерних элементов.
    public function getChildrenOf($id, $count = 1000, $offset = 0)
    {
        $where = ($id === null) ? 'IS NULL' : '= '.$id;
        $sth = $this->pdo->prepare("SELECT * FROM `node` WHERE `parent_id` {$where} ORDER BY `left` LIMIT ". intval($offset) .", ". intval($count));
        $sth->execute();
        return $this->getChildrenData($sth);
    }

    // Возвращает массив пригодных к использованию данных,
    // вытягивает значения из дополнительных полей.
    public function getChildrenData($sth, $fetch_extra = true, $fetch_att = false, $fetch_acc = true)
    {
        $result = array();

        if (is_string($sth)) {
          $sth = mcms::db()->prepare($sth);
          $sth->execute();
        }

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            self::populateData($row);
            if (empty($row['code']))
              $row['code'] = $row['id'];
            $result[$row['id']] = $row;
        }

        $sth->closeCursor();

        if ($fetch_extra and !empty($result)) {
            // Список идентификаторов, которые уже обработаны.
            $cached = array();

            // Читаем дополнительные поля из таблиц.
            foreach ($result as $item) {
              if (array_key_exists($item['id'], $cached))
                continue;

              if (!empty($item['class']) and ($fields = $this->getIndexedFields($item['class'])) !== null) {
                $fields = '`n`.`id`, `'. join('`, `t`.`', $fields) .'`';
                $nids = join(', ', array_keys($result));

                foreach ($this->pdo->getResultsK("id", "SELECT {$fields} FROM `node_{$item['class']}` `t` INNER JOIN `node` `n` ON `n`.`rid` = `t`.`rid` WHERE `n`.`lang` = 'ru' AND `n`.`id` IN ({$nids}) -- Tagger::getChildrenData()/\$fetch_extra") as $nid => $row) {
                  unset($row['id']);
                  $result[$nid] = $result[$nid] += $row;
                  $cached[$nid] = true;
                }
              }
            }
        }

        // Читаем прикреплённые документы.
        if ($fetch_att and $this->checkFiles($result)) {
          $files = $this->pdo->getResults($sql = "SELECT `r`.`tid` AS `__node_id`, `r`.`key` AS `__key`, "
            ."`n`.`id`, `n`.`class`, `n`.`code`, `n`.`created`, `n`.`updated`, `n`.`lang`, `n`.`published`, `v`.`name`, `v`.`data`, `f`.* "
            ."FROM `node` `n` INNER JOIN `node__rel` `r` ON `r`.`nid` = `n`.`id` "
            ."INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid` "
            ."INNER JOIN `node_file` `f` ON `f`.`rid` = `n`.`rid` WHERE `r`.`tid` IN (". join(", ", array_keys($result)) .") AND `class` = 'file' AND `deleted` = 0 -- Tagger::getChildrenData()/\$fetch_att");

          foreach ($files as $nid => $file) {
            $nid = $file['__node_id'];
            unset($file['__node_id']);

            if (empty($file['__key']))
              $key = $file['id'];
            else {
              if ($file['__key'] == 'file1___bebop')
                $key = $file['id'];
              else
                $key = $file['__key'];
              unset($file['__key']);
            }

            self::populateData($file);

            if (array_key_exists($nid, $result)) {
              $result[$nid]['files'][$key] = $file;
            }
          }
        }

        return $result;
    }

    private function checkFiles(array $result)
    {
      if (empty($result))
        return false;

      $classes = array();

      foreach ($result as $node)
        if (!in_array($node['class'], $classes))
          $classes[] = $node['class'];

      foreach ($classes as $class)
        if (TypeNode::checkHasFiles($class))
          return true;

      return false;
    }

    // Возвращает список дополнительных (индексированных) полей для класса.
    private function getIndexedFields($class)
    {
      $fields = array();
      $schema = TypeNode::getSchema($class);

      if (empty($schema['fields']))
        return null;

      foreach ($schema['fields'] as $field => $finfo)
        if (!empty($finfo['indexed']) and !self::isReservedName($field))
          $fields[] = $field;

      return empty($fields) ? null : $fields;
    }

    // Возвращает количество прямых потомков объекта.
    public function countChildrenOf($id)
    {
        $sub = ($id === null) ? "`parent_id` IS NULL" : "`parent_id` = {$id}";
        $sth = $this->pdo->prepare("SELECT COUNT(*) FROM `node` WHERE {$sub}");
        $sth->execute();
        return $sth->fetchColumn(0);
    }

    // Возвращает отдельный элемент.
    public function getObject($id, $system = false, $fetch_att = false)
    {
        $sth = $this->pdo->prepare("SELECT `n`.`id`, `n`.`parent_id`, `n`.`class`, `n`.`code`, `n`.`created`, `n`.`updated`, `n`.`lang`, `n`.`rid`, `n`.`uid`, `n`.`published`, `n`.`left`, `n`.`right`, `r`.`name`, `r`.`data` FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` WHERE `n`.`id` = :id");
        $sth->execute(array(':id' => $id));

        $data = $this->getChildrenData($sth, true, $fetch_att);

        return empty($data[$id]) ? null : $data[$id];
    }

    public function getObjects(array $keys)
    {
        if (empty($keys))
            return array();

        $sth = $this->pdo->prepare("SELECT * FROM `node` WHERE `id` IN (". join(', ', $keys) .")");
        $sth->execute();
        return $this->getChildrenData($sth);
    }

    // Возвращает класс объекта.
    public function getObjectClass($id)
    {
        static $sth = null;

        if ($sth === null)
            $sth = $this->pdo->prepare("SELECT `class` FROM `node` WHERE `id` = :id");

        $sth->execute(array(':id' => $id));
        $res = $sth->fetchColumn(0);
        $sth->closeCursor();
        return $res;
    }

    static private function populateData(array &$row)
    {
        if (!empty($row['data']) and is_array($extra = @unserialize($row['data']))) {
            foreach ($extra as $k => $v)
              if (!array_key_exists($k, $row))
                $row[$k] = $v;
        }

        if (array_key_exists('data', $row))
            unset($row['data']);
    }

    // Возвращает список объектов, начиная с указанного.
    public function getObjectTree($root, $published = 0)
    {
      $root = intval($root);

      $sql = "SELECT `n`.`id`, `n`.`code`, `n`.`parent_id`, `n`.`class`, `n`.`published`, `n`.`deleted`, `r`.`name`, `r`.`data` "
        ."FROM `node` `n`, `node__rev` `r`, `node` `span` "
        ."WHERE `n`.`rid` = `r`.`rid` "
        ."AND `n`.`lang` = 'ru' "
        ."AND `span`.`id` = {$root} "
        ."AND `n`.`left` >= `span`.`left` "
        ."AND `n`.`right` <= `span`.`right` "
        ."ORDER BY `n`.`left`"
        ;

      $data = $this->getChildrenData($sql, true, true);

      $tree = bebop_make_tree($data, 'id', 'parent_id');

      return $tree;
    }

    // Формируем открывающий тэг для объекта.
    private static function getObjectXML($row)
    {
        $output = '<'.$row['class'];

        if (!empty($row['data'])) {
            $extra = @unserialize($row['data']);
            unset($row['data']);
            $row = array_merge($row, $extra);
        }

        foreach ($row as $k => $v) {
            if ($k !== 'class' and $k !== 'depth' and $k !== 'parent_id' and $k !== 'created' and $k !== 'updated' and $v != '')
                $output .= ' '.$k.'="'.htmlspecialchars($v, ENT_QUOTES).'"';
        }

        $output .= '>';
        return $output;
    }

    // Создание нового объекта.
    public function createNode($class, $parent_id, array $data = null)
    {
      if (!$this->hasClass($class))
        throw new InvalidArgumentException("Class {$class} not found.");

      $data['parent_id'] = $parent_id;
      $data['class'] = $class;

      $this->nodeSave($data);

      return $data['id'];
    }

    private function nodeCreate(array &$node)
    {
      if (!empty($node['id']))
        throw new InvalidArgumentException("This node already has an id.");

      if (empty($node['parent_id'])) {
        $lft = intval($this->pdo->getResult("SELECT MAX(`right`) FROM `node`")) + 1;
        $rgt = $lft + 1;
      } else {
        $lft = intval($this->pdo->getResult("SELECT `right` FROM `node` WHERE `id` = :parent_id", array(':parent_id' => $node['parent_id'])));
        $rgt = $lft + 1;

        try {
          $this->pdo->exec("UPDATE `node` SET `left` = `left` + 2 WHERE `left` >= :left ORDER BY `left` DESC", array(':left' => $lft));
          $this->pdo->exec("UPDATE `node` SET `right` = `right` + 2 WHERE `right` >= :left ORDER BY `right` DESC", array(':left' => $lft));
        } catch (PDOException $e) {
          throw new Exception("Could not allocate left/right span for a node of class `{$node['class']}` with parent_id = {$node['parent_id']}");
        }
      }

      $node['id'] = $this->pdo->getResult("SELECT MAX(`id`) FROM `node`") + 1;

      $this->pdo->exec("INSERT INTO `node` (`id`, `lang`, `parent_id`, `class`, `left`, `right`, `uid`, `published`) "
        ."VALUES(:nid, :lang, :parent_id, :class, :left, :right, :uid, :published)",
        array(
          ':nid' => $node['id'],
          ':lang' => 'ru',
          ':parent_id' => @$node['parent_id'],
          ':class' => @$node['class'],
          ':left' => $lft,
          ':right' => $rgt,
          ':uid' => empty($node['uid']) ? null : $node['uid'],
          ':published' => empty($node['published']) ? 0 : 1,
          ));
    }

    public function nodeSave(&$node, $rev = null)
    {
      if (empty($node['lang']))
        throw new InvalidArgumentException("Trying to save a node ({$node['id']}) which does not have a language id; data: ". var_export($node, true));

      if (!is_array($node))
        throw new InvalidArgumentException("Tagger::nodeSave() was passed not an array: ". var_export($node, true));

      if (empty($node['class']) and !empty($node['id']))
          $node['class'] = $this->getObjectClass($node['id']);
      else
          self::checkClassName($node['class']);

      // Создаём новый объект?
      if (empty($node['id']))
        $this->nodeCreate($node);

      $this->nodeSaveRevision($node, $rev);
    }

    // Сохраняет документ, создавая новую ревизию.
    private function nodeSaveRevision(array &$node, $forcerev = null)
    {
      // Всё, что не укладывается в нормальные поля, складываем сюда и сериализуем.
      $revdata = array();

      $extra = array('fields' => array('rid'), 'params' => array('rid' => null));
      $schema = TypeNode::getSchema($node['class']);

      // Добавляем отсутствующие поля, описанные в схеме.
      foreach ($schema['fields'] as $field => $meta) {
        $ctt = mcms_ctlname($meta['type']);

        if ($ctt == 'BoolControl')
          $value = empty($node[$field]) ? 0 : 1;

        elseif ($ctt == 'FloatControl')
          $value = floatval($node[$field]);

        elseif ($ctt == 'NodeLinkControl')
          $value = (empty($node[$field]) and empty($meta['required'])) ? null : intval($node[$field]);

        elseif ($ctt == 'NumberControl')
          $value = (empty($node[$field]) and empty($meta['required'])) ? null : $node[$field];

        elseif ($ctt == 'DateTimeControl')
          $value = (empty($node[$field]) and empty($meta['required'])) ? null : $node[$field];

        elseif (!empty($node[$field]))
          $value = $node[$field];

        elseif (array_key_exists('default', $meta))
          $value = $meta['default'];

        else
          $value = null;

        $node[$field] = $value;
      }

      // Раскладываем поля объекта по полочкам.
      foreach ($node as $k => $v) {
        if ($this->isReservedName($k))
          continue;

        if (!empty($schema['fields'][$k]['indexed'])) {
          $extra['fields'][] = $k;
          $extra['params'][$k] = $v;
        }

        else {
          $revdata[$k] = $v;
        }
      }

      // Сохраняем ключевую часть ревизии.
      try {
        if (!array_key_exists('uid', $node))
          $node['uid'] = mcms::user()->id;
        if (empty($node['uid']))
          $node['uid'] = null;

        // Собираем данные для отправления.
        $save = array(
          'rid' => $forcerev,
          'nid' => $node['id'],
          'uid' => $node['uid'],
          'name' => $node['name'],
          'data' => empty($revdata) ? null : serialize($revdata),
          'created' => 'UTC_TIMESTAMP()',
          );

        $node['rid'] = $this->rowUpdate('node__rev', 'rid', $save);
      } catch (PDOException $e) {
        throw new Exception(t("Не удалось сохранить основные свойства объекта %nid, сообщение: %message.", array(
          '%nid' => $node['id'],
          '%message' => bebop_is_debugger() ? $e->getMessage() : t('недоступно'),
          )));
      }

      // Сохраняем дополнительные данные.
      if ($this->classHasExtraFields($node['class'])) {
        try {
          $extra['params']['rid'] = $node['rid'];
          $key = $forcerev ? 'rid' : '#nokey';

          $this->rowUpdate('node_'. $node['class'], $key, $extra['params'], null, true);
        } catch (PDOException $e) {
          throw new Exception(t("Не удалось сохранить расширенные данные для объекта %nid@%rid, сообщение: %message.", array(
            '%nid' => $node['id'],
            '%rid' => $node['rid'],
            '%message' => bebop_is_debugger() ? $e->getMessage() : t('сообщение недоступно'),
            )));
        }
      }

      // Обновляем заголовок.
      try {
        if (empty($node['code']))
          $node['code'] = null;

        // Если документ опубликован -- ревизия создаётся в качестве черновика,
        // без изменения активной ревизии, в противном случае новая ревизия
        // становится активной (чтоб новые документы имели хоть одну ревизию).
        if (false and !empty($node['published']))
          unset($node['rid']);

        if (empty($node['created']))
          $node['created'] = 'UTC_TIMESTAMP()';

        $node['updated'] = 'UTC_TIMESTAMP()';
        $node['deleted'] = empty($node['deleted']) ? 0 : 1;

        $this->rowUpdate('node', array('id', 'lang'), $node, array('id', 'rid', 'nid', 'lang', 'code', 'created', 'updated', 'deleted'));
      } catch (PDOException $e) {
        throw new Exception(t("Could not update header for node %id, message: %message", array(
          '%id' => $node['id'],
          '%message' => bebop_is_debugger() ? $e->getMessage() : 'unavailable.',
          )));
      }
    }

    private static function isSafeDate($k, $v)
    {
      if ($k !== 'created' && $k !== 'updated')
        return false;
      if ($v !== 'UTC_TIMESTAMP()' && $v !== 'NOW()')
        return false;
      return true;
    }

    private function rowInsert($table, array $data, $replace)
    {
      $fields = $values = $params = array();

      foreach ($data as $k => $v) {
        $fields[] = $k;

        if (self::isSafeDate($k, $v)) {
          $values[] = $v;
        } else {
          $values[] = ':'. $k;
          $params[$k] = $v;
        }
      }

      $sql = $replace ? 'REPLACE' : 'INSERT';
      $sql .= " INTO `{$table}` (`". join('`, `', $fields) ."`) VALUES (". join(', ', $values) .")";

      $this->pdo->exec($sql, $params);

      return $this->pdo->lastInsertId();
    }

    // Обновление или добавление записи.
    private function rowUpdate($table, $key, array $data, array $filter = null, $replace = false)
    {
      $set = array();
      $where = array();

      // Удаляем ненужные поля.
      if ($filter !== null)
        $data = array_intersect_key($data, array_flip($filter));

      // Добавляем новую запись.
      if ($replace or (is_string($key) and empty($data[$key])))
        return $this->rowInsert($table, $data, $replace);

      // Обрабатываем полученные поля.
      foreach ($data as $k => $v) {
        if (self::isSafeDate($k, $v)) {
          $pair = "`{$k}` = {$v}";
          unset($data[$k]);
        } else {
          $pair = "`{$k}` = :{$k}";
        }

        if ((is_array($key) and in_array($k, $key)) or $key == $k)
          $where[] = $pair;
        else
          $set[] = $pair;
      }

      // Если ключей несколько или один из них указан -- обновляем запись.
      $sql = "UPDATE `{$table}` SET ". join(', ', $set) ." WHERE ". join(' AND ', $where);

      // Поехали.
      $this->pdo->exec($sql, $data);

      if (is_array($key))
        $key = array_shift($key);

      // Уехали.
      return $data[$key];
    }

    private function classHasExtraFields($class)
    {
      $schema = TypeNode::getSchema($class);

      foreach ($schema['fields'] as $k => $v)
        if (!empty($v['indexed']))
          return true;

      return false;
    }

    // Обновление данных в основной таблице (node), создание документа.
    private function nodeSaveHeader(array &$node)
    {
      // Сюда складываем значения сохраняемых полей.
      $fields = array('data');
      $params = array(':data' => null);

      // Добавляем идентификатор в условие, если он есть.
      if (!empty($node['id'])) {
        $fields[] = 'id';
        $params[':id'] = $node['id'];
      }

      // Схема нужна для игнорирования полей, которые пойдут в дополнительную таблицу.
      $schema = TypeNode::getSchema($node['class']);

      // Обрабатываем поля записи.
      foreach ($node as $k => $v) {
        switch ($k) {
          case 'id':
            break;

          case 'parent_id':
          case 'class':
          case 'left':
          case 'right':
            if (!empty($node['id']))
              break;

          case 'code':
          case 'name':
          case 'created':
          case 'updated':
            $fields[] = $k;
            $params[':'. $k] = $v;
            break;

          default:
            if (!empty($schema['fields'][$k]['indexed']))
              break;

            if (substr($k, 0, 1) == '#')
              break;

            if ($v !== null and $v !== '' and !(is_array($v) and empty($v)))
              $params[':data'][$k] = $v;
        }
      }

      // Сериализуем дополнительные данные.
      if ($params[':data'] !== null)
        $params[':data'] = serialize($params[':data']);

      $node['id'] = $this->rowUpdateOrInsert('node', 'id', $fields, $params);
    }

    private function nodeSaveExtra(array $node)
    {
      // Список полей и параметров будущего запроса.
      $fields = array('id');
      $params = array(':id' => $node['id']);

      // Схема этого класса, для быстрого доступа.
      $schema = TypeNode::getSchema($node['class']);

      // Обрабатываем все требуемые поля.
      foreach ($this->config['classes'][$node['class']]['fields'] as $field => $info) {
        if (empty($info['indexed']))
          continue;

        $fields[] = $field;

        $value = empty($node[$field]) ? null : $node[$field];

        if ($value === null or ($value = trim($value)) === '') {
          if (empty($schema['fields'][$field]['required']))
            $value = null;
          elseif (mcms_ctlname($schema['fields'][$field]['type']) == 'BoolControl')
            $value = 0;
          elseif (!empty($schema['fields'][$field]['default']))
            $value = $schema['fields'][$field]['default'];
        }

        $params[':'. $field] = $value;
      }

      // Обновляем запись если набрали что-нибудь.
      if (count($fields) > 1) {
        $this->rowUpdateOrInsert('node_'. $node['class'], 'id', $fields, $params);
      }
    }

    // Обновляет запись в указанной таблице, если её нет -- добавляет новую.
    private function rowUpdateOrInsert($table, $keyname, array $fields, array $params)
    {
      $pairs = array();

      foreach ($fields as $field)
        if ($field != $keyname)
          $pairs[] = "`{$field}` = :{$field}";

      // Проверка на наличие записи.  PDOStatement->rowCount() не подходит потому, что
      // возвращает количество изменённых строк.  При отсутствии модификации, даже если
      // запись с таким id есть, мы получим 0.
      if (empty($params[':'. $keyname]))
        $exists = false;
      else
        $exists = $this->pdo->getResult("SELECT COUNT(*) FROM `{$table}` WHERE `{$keyname}` = :key -- Tagger::nodeUpdateOrInsert()", array(':key' => $params[':'. $keyname]));

      try {
        if ($exists) {
          $sth = $this->pdo->prepare($sql = "UPDATE `{$table}` SET ". join(', ', $pairs) ." WHERE `{$keyname}` = :{$keyname} -- Tagger::nodeUpdateOrInsert()");
          $sth->execute($params);

          return $params[':'. $keyname];
        }

        else {
          $sth = $this->pdo->prepare($sql = "INSERT INTO `{$table}` (`". join('`, `', $fields) ."`) VALUES (:". join(', :', $fields) .") -- Tagger::nodeUpdateOrInsert()");
          $sth->execute($params);

          return $this->pdo->lastInsertId();
        }
      } catch (PDOException $e) {
        throw new Exception("Could not update node {$params[':id']}: ". $e->getMessage() .", SQL: {$sql}, parameters: ". var_export($params, true));
      }
    }

    public function expandNode($nid, $children)
    {
      $pdo = mcms::db();

      $meta = $pdo->getResults("SELECT `left`, `right` FROM `node` WHERE `id` = :id", array(':id' => $nid));
      if (empty($meta))
        throw new Exception("Node {$nid} not found.");

      $pdo->exec("UPDATE `node` SET `left` = `left` + :span WHERE `left` >= :right ORDER BY `left` DESC", $args = array(':span' => $children * 2, ':right' => $meta[0]['right']));
      $pdo->exec("UPDATE `node` SET `right` = `right` + :span WHERE `right` >= :right ORDER BY `left` DESC", $args);
    }

    // Проверка имени класса на валидность.
    public static function checkClassName($name)
    {
        if (empty($name))
          throw new ValidationException('name');
        if (strspn(strtolower($name), 'abcdefghijklmnopqrstuvwxyz0123456789_') != strlen($name))
          throw new ValidationException('name', "Внутреннее имя типа документа может содержать только буквы латинского алфавита, арабские цифры и символ подчёркивания (&laquo;_&raquo;).");
    }

    // Получение списка возможных классов.
    public function getClasses()
    {
        $result = array();

        foreach (TypeNode::getSchema() as $class => $meta)
          $result[$class] = $meta['title'];

        return $result;
    }

    public function hasClass($name)
    {
      return array_key_exists($name, TypeNode::getSchema());
    }

    public function isReservedName($fieldname)
    {
        return in_array($fieldname, array(
          'id',
          'nid',
          'rid',
          'parent_id',
          'class',
          'code',
          'left',
          'right',
          'created',
          'updated',
          'lang',
          'uid',
          'name',
          'data',
          'published',
          ));
    }

    private function getNeighbour($parent_id, $index, $direction)
    {
        if ('<' == $direction) {
            $sort = 'DESC';
            $side = 'right';
        } else {
            $sort = 'ASC';
            $side = 'left';
        }

        $sql = "SELECT * FROM node WHERE class = 'tag' AND parent_id = " . intval($parent_id) . " AND `{$side}` {$direction}= " . intval($index) . " AND `deleted` = 0 ORDER BY `{$side}` {$sort} LIMIT 1";

        //var_dump($sql);
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        $data = $this->getChildrenData($sth, true);
        foreach ($data as $k => $v) {
            return $v;
        }
    }

    private function getNestedNodes($left, $right)
    {
        $sql = "SELECT * FROM node WHERE class = 'tag' AND `left` >= " . intval($left) . " AND `right` <= " . intval($right) . " ORDER BY `left` ASC";
        //var_dump($sql);
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        $data = $this->getChildrenData($sth, true);

        return $data;
    }

    private function getNeighbourLeft($parent_id, $index)
    {
        return $this->getNeighbour($parent_id, $index, '<');
    }

    private function getNeighbourRight($parent_id, $index)
    {
        return $this->getNeighbour($parent_id, $index, '>');
    }

    private function getOffset()
    {
        $sql = "SELECT MAX(`right`) * 2 + 10 AS `offset` FROM `node`";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        $offset = $sth->fetchColumn(0);

        return intval($offset);
        //return intval($data['']['offset']);
    }

    private function nodeMove($id, $direction)
    {
        // Получаем объект для перемещения ...
        $node = $this->getObject($id, true);

        if (false === $node) {
            // Нэт билэт...
            throw new Exception("No such node {$id}");
        }

        $parent_id = $node['parent_id'];
        $left = $node['left'];
        $right = $node['right'];
        $nodeSize = ($right - $left) + 1;
        // Получаем все вложения ноды, которую перемещаем
        $nestedNodes = $this->getNestedNodes($left, $right);

        // Если двигаем наверх - получаем массив соседей слева
        if (self::MOVE_UP == $direction) {
            $neighbourNode = $this->getNeighbourLeft($parent_id, ($left - 1));
            $operand1 = '-';
            $operand2 = '+';
        }

        // Если двигаем вниз - получаем массив соседей справа
        elseif (self::MOVE_DOWN == $direction) {
            $neighbourNode = $this->getNeighbourRight($parent_id, ($right + 1));
            $operand1 = '+';
            $operand2 = '-';
        }

        // Нет соседей - нет перемещения
        if (0 == sizeof($neighbourNode)) {
            return null;
        }

        // Нужно получить смещение, на которое будут временно перемещены движимые ноды, чтобы не получить ошибку дублирующихся ключей
        $offset = $this->getOffset();

        $neighbourRight = $neighbourNode['right'];
        $neighbourLeft = $neighbourNode['left'];
        $neighbourSize = ($neighbourRight - $neighbourLeft) + 1;

        // Получаем все вложения
        $nestedNodesNeighbour = $this->getNestedNodes($neighbourLeft, $neighbourRight);

        // Получаем идентификаторы вложений
        foreach($nestedNodes as $k => $v) {
            $nestedID[] = $nestedNodes[$k]['id'];
        }

        // Переносим первую партию нод с учетом смещения
        $sql = "UPDATE `node` SET `left` = `left` + {$offset}, `right` = `right` + {$offset} WHERE id IN (" . join(',', $nestedID) . ") ORDER BY `left` DESC";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        // Получаем идентификаторы вложений соседа
        foreach($nestedNodesNeighbour as $k => $v) {
            $nestedNeighbourID[] = $nestedNodesNeighbour[$k]['id'];
        }

        // Переносим партию нод соседа с учетом смещения
        $sql = "UPDATE `node` SET `left` = `left` + {$offset}, `right` = `right` + {$offset} WHERE id IN (" . join(',', $nestedNeighbourID) . ") ORDER BY `left` DESC";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        // Вертаем взад первую партию нод с учетом смещения и размера соседа
        $sql = "UPDATE `node` SET `left` = `left` - {$offset} {$operand1} {$neighbourSize}, `right` = `right` - {$offset} {$operand1} {$neighbourSize} WHERE id IN (" . join(',', $nestedID) . ") ORDER BY `left` ASC";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        // Вертаем взад партию нод соседа с учетом смещения и размера первой партии нод
        $sql = "UPDATE `node` SET `left` = `left` - {$offset} {$operand2} {$nodeSize}, `right` = `right` - {$offset} {$operand2} {$nodeSize} WHERE id IN (" . join(',', $nestedNeighbourID) . ") ORDER BY `left` ASC";
        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        return true;
    }

    public function nodeMoveUp($id)
    {
        $this->nodeMove($id, self::MOVE_UP);
    }

    public function nodeMoveDown($id)
    {
        $this->nodeMove($id, self::MOVE_DOWN);
    }

    // Удаление данных по несуществующим полям.
    public function clean(array $object)
    {
        if (empty($object['class']))
          return $object;

        if ($object['class'] == 'file' or $object['class'] == 'type')
          return $object;

        $schema = TypeNode::getSchema($object['class']);

        foreach ($object as $k => $v) {
            if ($this->isReservedName($k))
                continue;
            if (!empty($schema['fields'][$k]))
                continue;
            unset($object[$k]);
        }

        return $object;
    }

    // Публикация документов.
    public function nodePublish(array $nodes, $mode = true)
    {
        if (!empty($nodes)) {
            $mode = $mode ? 1 : 0;
            $pdo = mcms::db();

            $nids = join(", ", $nodes);

            // Проставляем флаг для быстрого доступа.
            $pdo->exec("UPDATE `node` SET `published` = {$mode} WHERE `id` IN ({$nids})");
        }
    }

    // Возвращает документы для указанных разделов.
    public function getDocumentsFor(array $tags, array $options = null, array $filters = null)
    {
      $result = array();
      $nodes = Node::find(array('tags' => $tags, 'published' => 1));

      foreach ($nodes as $nid => $node)
        $result[$nid] = $node->getRaw();

      return $result;
    }

  // Возвращает документы с указанными идентификаторами.
  public function getDocuments(array $ids)
  {
    $result = array();

    if (!empty($ids)) {
      $nids = $codes = $where = array();

      foreach ($ids as $id)
        if (is_numeric($id))
          $nids[] = $id;
        else
          $codes[] = $id;

      if (!empty($nids))
        $where[] = "`id` IN (". join(", ", $nids) .")";
      if (!empty($codes))
        $where[] = "`code` IN ('". join("', '", $codes) ."')";

      $sth = mcms::db()->prepare("SELECT * FROM `node` WHERE ". join(" AND ", $where));
      $sth->execute();
      $result = $this->getChildrenData($sth);
    }

    return $result;
  }

  // Возвращает права доступа на класс.  Поля массива: uid, name, c, r, u, d.
  public function getClassPermissions($name)
  {
      $result = array();

      // FIXME: переписать.

      return $result;
  }

  public function setClassPermissions($class, $perms)
  {
    // FIXME: переписать
  }

  // Проверяет, есть ли у пользователя доступ на операцию/класс.
  public function checkUserAccess(User $user, $class, $mode)
  {
      if (!$this->hasClass($class))
          return false;

      // FIXME: переписать.

      return true;
  }

  // Возвращает идентификатор анонимного пользователя.
  private function getVisitorsId()
  {
    $key = 'group:visitors:id';
    $gid = mcms::cache($key);

    if ($gid === false) {
      $node = Node::load(array('class' => 'group', 'group.login' => 'Visitors'));
      $gid = $node->id;
      mcms::cache($key, $gid);
    }

    return $gid;
  }
};
