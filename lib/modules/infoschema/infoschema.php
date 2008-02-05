<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class InfoSchema
{
    // Соединение с базой данных.
    private $pdo = null;

    // Имя текущей базы данных.  Используется только при обновлении схемы.
    private $dbname = null;

    // Конструктор.
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public static function getInstance()
    {
        static $sm = null;
        if ($sm === null)
            $sm = new InfoSchema(mcms::db());
        return $sm;
    }

    // Получение имени текущей базы данных.
    public function getDBName()
    {
        if ($this->dbname === null)
            $this->dbname = mcms::db()->getResult("SELECT database() -- InfoSchema::getDBName()");
        return $this->dbname;
    }

    // Проверяет указанную таблицу на соответствие структуре.
    public function checkTable($table, array $structure)
    {
        $changes = $this->checkTableColumns($table, $structure);
        if (!empty($structure['keys']))
            $this->checkTableKeys($table, $structure['keys']);
        return $changes;
    }

    private function checkTableColumns($table, array &$structure)
    {
        $changes = 0;
        $data = $this->getTableFields($table);

        // Изменяем существующую таблицу.
        if ($data !== null) {
            foreach ($structure['columns'] as $field => $info) {
                // Базовая инструкция.
                if (array_key_exists($field, $data))
                    $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$field}`";
                else
                    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$field}`";

                if (($command = $this->getColumnDef($field, $structure, $data)) !== null) {
                    $sql .= $command;

                    try {
                        $this->pdo->exec($sql);
                        $changes++;
                    } catch (PDOException $e) {
                        throw new Exception(sprintf("ERROR updating column `%s`.`%s` with SQL: %s, old data: %s", $table, $field, $sql, var_export($data[$field], true)));
                    }
                }
            }
        }

        // Создаём новую таблицу.
        else {
            $lines = array();

            foreach (array_keys($structure['columns']) as $field)
                $lines[] = "`{$field}`". $this->getColumnDef($field, $structure);

            $sql = "CREATE TABLE `{$table}` (". join(", ", $lines) .") TYPE=InnoDB CHARSET=utf8";

            $this->pdo->exec($sql);
            $changes++;
        }

        return $changes;
    }

    private function checkTableKeys($table, array $keys)
    {
        $changes = 0;

        $data = $this->pdo->getResult("SHOW CREATE TABLE `{$table}`");
        $data = $data['Create Table'];

        foreach ($keys as $k => $v) {
            if (!array_key_exists('columns', $v))
                $v['columns'] = array($k);

            // Отдельно проверяем первичные ключи.
            if (!empty($v['pk']))
                $changes += $this->checkTableKey($table, $data, "PRIMARY KEY  (%fields)", $k, $v);

            switch ($v['type']) {
            case 'primary':
                $sql = null;
                break;

            case 'unique':
                $sql = "UNIQUE KEY `{$k}` (%fields)";
                break;

            case 'simple':
                $sql = "KEY `{$k}` (%fields)";
                break;

            case 'foreign':
                $sql = "FOREIGN KEY (`{$k}`) REFERENCES `{$v['table']}` (`{$v['column']}`)";
                if (!empty($v['delete']))
                    $sql .= " ON DELETE CASACADE";
                if (!empty($v['update']))
                    $sql .= " ON UPDATE CASCADE";

                if (strstr($data, $sql) === false) {
                    $k = null;
                    break;
                }

                $sql = "CONSTRAINT `{$k}_fk` ". $sql;
                break;

            default:
                throw new InvalidArgumentException("Unknown key type: ". $v['type']);
            }

            if ($k === null)
                continue;

            if ($sql !== null)
                $changes += $this->checkTableKey($table, $data, $sql, $k, $v);
        }

        return $changes;
    }

    private function checkTableKey($table, $data, $sql, $k, $v)
    {
        $changes = 0;

        // Формируем окончательную инструкцию.
        $sql = str_replace('%fields', '`'. join('`,`', $v['columns']) .'`', $sql);

        // Создаём ключ, если его нет.
        if (strstr($data, $sql) === false) {
            try {
                // Если уже есть другой ключ с таким именем -- удаляем.
                // FIXME: почему-то первичный ключ попадает сюда как 'foreign'.
                if (($v['type'] == 'primary') or (substr($sql, 0, 12) == 'PRIMARY KEY ')) {
                    // Если именно этот ключ уже есть -- ничего не трогаем.
                    if (strstr($data, $sql) !== false)
                        return $changes;

                    if (strstr($data, "PRIMARY KEY") !== false) {
                        $this->pdo->exec($command = "ALTER TABLE `{$table}` DROP PRIMARY KEY");
                        $changes++;
                    }
                }
                
                elseif (strstr($data, "KEY `{$k}`") !== false and strstr($data, "FOREIGN KEY (`{$k}`)") === false) {
                    $this->pdo->exec($command = "ALTER TABLE `{$table}` DROP KEY `{$k}`");
                    $changes++;
                }

                $this->pdo->exec($command = "ALTER TABLE `{$table}` ADD ". $sql);
                $changes++;
            } catch (PDOException $e) {
                throw new Exception("Could not add a key: {$command}");
            }
        }

        return $changes;
    }

    // Возвращает описание полей таблицы.
    public function getTableFields($table)
    {
        $result = array();

        foreach ($this->pdo->getResults("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = :db AND `TABLE_NAME` = :table ORDER BY `ORDINAL_POSITION`", array(':db' => $this->getDBName(), ':table' => $table)) as $column) {
            $name = $column['COLUMN_NAME'];
            unset($column['COLUMN_NAME']);

            $result[$name] = $column;
        }

        return empty($result) ? null : $result;
    }

    private function getColumnDef($field, array &$structure, array $data = null)
    {
        $sql = '';

        // Отслеживаем изменения.
        $update = false;

        // Шорткат.
        $info = $structure['columns'][$field];

        // Проверяем тип.
        if ($info['type'] != @$data[$field]['COLUMN_TYPE'])
            $update = 'type';
        $sql .= " {$info['type']}";

        // Форсируем required для первичных ключей.
        if (!empty($info['pk']))
            $info['required'] = true;

        // Проверяем обязательность.
        if (empty($info['required']) and @$data[$field]['IS_NULLABLE'] == 'NO')
            $update = 'required';
        elseif (!empty($info['required']) and @$data[$field]['IS_NULLABLE'] == 'YES')
            $update = 'required';
        $sql .= empty($info['required']) ? " NULL" : " NOT NULL";

        // Проверяем дефолтное значение (жёсткое сравнение потому, что сравниваем NULL).
        // FIXME: what?  отключено, потому что получается изменение 0 на 0 в node_file.filesize.
        if (array_key_exists('default', $info) and $info['default'] != @$data[$field]['COLUMN_DEFAULT'])
            $update = 'default';
        if (array_key_exists('default', $info) and empty($info['pk']))
            $sql .= " DEFAULT {$info['default']}";

        // Проверяем автоинкремент.
        if (!empty($info['autoincrement']) != (@$data[$field]['EXTRA'] == 'auto_increment'))
            $update = 'auto_increment';
        if (!empty($info['autoincrement']))
            $sql .= " PRIMARY KEY AUTO_INCREMENT";

        // Копируем индексы.
        if (!empty($info['pk']))
            $structure['keys'][$field]['type'] = 'primary';
        elseif (!empty($info['unique']))
            $structure['keys'][$field]['type'] = 'unique';
        elseif (!empty($info['indexed']))
            $structure['keys'][$field]['type'] = 'simple';

        if (!empty($info['reference'])) {
            $structure['keys'][$field] = $info['reference'];
            $structure['keys'][$field]['type'] = 'foreign';

            if (!empty($info['pk']))
                $structure['keys'][$field]['pk'] = true;
        }

        if ($update)
            return $sql;

        return null;
    }
};

class TableInfo implements iModuleConfig
{
    protected static $dbname = null;

    protected $isnew;
    protected $name;
    protected $columns;
    protected $alter;

    public function __construct($name)
    {
        if (null === self::$dbname)
            self::$dbname = mcms::db()->getResult("SELECT database() -- TableInfo::__construct()");

        $this->name = $name;
        $this->columns = $this->alter = array();
        $this->isnew = false;

        $info = $this->scan($name);
    }

    // Получение информации о таблице.
    protected function scan($name)
    {
        try {
            $data = mcms::db()->getResults("DESCRIBE `{$name}`");

            foreach ($data as $c) {
                $this->columns[$c['Field']] = array(
                    'type' => $c['Type'],
                    'required' => 'NO' == $c['Null'],
                    'key' => $c['Key'],
                    'default' => $c['Default'],
                    'autoincrement' => strstr($c['Extra'], 'auto_increment') !== false,
                    );
            }
        } catch (PDOException $e) {
            $this->isnew = true;
        }
    }

    public function columnSet($name, array $options = null)
    {
        $spec = array(
            'type' => 'varchar(255)',
            'required' => false,
            'key' => null,
            'default' => null,
            'autoincrement' => false,
            );

        if (null !== $options) {
            // Удаляем лишние ключи.
            foreach (array_keys($options) as $key)
                if (!array_key_exists($key, $spec))
                    unset($options[$key]);

            $spec = array_merge($spec, $options);
        }

        $modify = array_key_exists($name, $this->columns);

        if ($this->needsUpdate($name, $spec)) {
            $this->columns[$name] = $spec;

            $this->addSql($name, $spec, $modify);
        }
    }

    private function needsUpdate($name, array $new)
    {
        if (!array_key_exists($name, $this->columns))
            return true;

        $old = $this->columns[$name];

        if (strcasecmp($new['type'], $old['type']))
            return true;

        if ($new['required'] != $old['required'])
            return true;

        if ($new['autoincrement'] != $old['autoincrement'])
            return true;

        if ($new['default'] !== $old['default'])
            return true;

        if ($new['key'] != $old['key'])
            return true;

        return false;
    }

    public function columnDel($name)
    {
        if ($this->columnExists($name)) {
            $this->alter[] = "DROP COLUMN `{$name}`";
            unset($this->columns[$name]);
        }
    }

    public function columnExists($name)
    {
        return array_key_exists($name, $this->columns);
    }

    // Возвращает true, если таблица на данный момент существует.
    public function exists()
    {
        return !$this->isnew;
    }

    // Форматирует код для изменения структуры таблицы.
    protected function addSql($name, array $spec, $modify)
    {
        if (!$this->isnew) {
            if ($modify)
                $sql = "MODIFY COLUMN ";
            else
                $sql = "ADD COLUMN ";
        }

        $sql .= "`{$name}` ";
        $sql .= $spec['type'];

        if ($spec['required'])
            $sql .= ' NOT NULL';
        else
            $sql .= ' NULL';

        if (null !== $spec['default'])
            $sql .= ' DEFAULT '. $spec['default'];

        if ($spec['autoincrement'])
            $sql .= ' auto_increment';

        if ('pri' == $spec['key'])
            $sql .= ' PRIMARY KEY';

        $this->alter[] = $sql;
    }

    public function commit()
    {
        if (null !== ($sql = $this->getSql()))
            mcms::db()->exec($sql);

        $this->alter = array();
    }

    public function getSql()
    {
        if (empty($this->alter))
            return null;

        if ($this->isnew)
            $sql = "CREATE TABLE `{$this->name}` (";
        else
            $sql = "ALTER TABLE `{$this->name}` ";

        $sql .= join(', ', $this->alter);

        if ($this->isnew)
            $sql .= ') CHARSET=utf8';

        if (!is_array($conf = mcms::modconf('infoschema')))
            $conf = array(
                'engine' => 'InnoDB',
                );

        if (!empty($conf['engine']))
            $sql .= ' ENGINE='. $conf['engine'];

        return $sql;
    }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());
    $form->addControl(new EnumControl(array(
      'value' => 'config_engine',
      'label' => t('Тип создаваемых таблиц'),
      'default' => t('По умолчанию'),
      'options' => array(
          'InnoDB' => 'InnoDB',
          'MyISAM' => 'MyISAM',
          ),
      )));

    return $form;
  }
};
