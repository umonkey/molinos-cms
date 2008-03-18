<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

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

        if ('pri' == $spec['key']) {
            if (!$modify)
                $sql .= ' PRIMARY KEY';
        } elseif (!empty($spec['key']) and $this->isnew) {
            $sql .= ", KEY (`{$name}`)";
        }

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

        if ($this->isnew) {
            $sql .= ') CHARSET=utf8';

            if (!is_array($conf = mcms::modconf('infoschema')))
                $conf = array(
                    'engine' => 'InnoDB',
                    );

            if (!empty($conf['engine']))
                $sql .= ' ENGINE='. $conf['engine'];
        }

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

  public static function hookPostInstall()
  {
  }
};
