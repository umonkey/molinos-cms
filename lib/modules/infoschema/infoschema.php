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
            $sm = new InfoSchema(PDO_Singleton::getInstance());
        return $sm;
    }

    // Получение имени текущей базы данных.
    public function getDBName()
    {
        if ($this->dbname === null)
            $this->dbname = PDO_Singleton::getInstance()->getResult("SELECT database() -- InfoSchema::getDBName()");
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
