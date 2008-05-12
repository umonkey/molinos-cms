<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class TableInfo
{
    protected static $dbname = null;

    protected $isnew;
    protected $name;
    protected $columns;
    protected $alter;

    public function __construct($name)
    {
      self::$dbname = mcms::db()->getDbName();

      $this->name = $name;

      $this->coldel = $this->index = $this->columns = $this->alter = array();
      $this->isnew = false;
      $info = $this->scan($name);
    }

    // Получение информации о таблице.
    protected function scan($name)
    {
      $this->columns = mcms::db()->getTableInfo($name);

      if (!is_array($this->columns)){
         $this->isnew = true;
         $this->columns = array();
      }
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function columnCount()
    {
        return count($this->columns);
    }

    public function setNew($n)
    {
      $this->isnew = $n;
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
        // $this->coldel[] = $name;
        $this->alter[] = "DROP COLUMN `{$name}`";

        unset($this->columns[$name]);

        /*
        // TODO: это очень нужно?  при удалении колонки индекс сам не удалится?
        // Кроме того, изменять структуру здесь нельзя, надо только формировать
        // инструкции, а выполнять их будут в commit() или после getSQL().
        $sql = "DROP INDEX IF EXISTS `IDX_".$this->name."_".$name."`";
        mcms::db()->exec($sql);
        */
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
    public function addSql($name, array $spec, $modify)
    {
      list($sql, $ix) = mcms::db()->addSql($name, $spec, $modify, $this->isnew);

      $this->alter[] = $sql;

      if ($ix)
        $this->index[] = $ix;
    }

    public function commit()
    {
      $tblname = $this->name;

      if (!empty($this->coldel)) {
         mcms::db()->dropColumn($tblname, $this->coldel, $this->columns);
      } else {
        if (null !== ($sql = $this->getSql())) {
          mcms::db()->exec($sql);
        }
      }

      // Добавим индексы
      // FIXME: нет проверки на существование индекса.
      /*
      for ($i = 0; $i < count($this->index); $i++) {
        $el = $this->index[$i];
        $sql = "CREATE INDEX `IDX_{$tblname}_{$el}` on `{$tblname}` (`{$el}`)";
        mcms::db()->exec($sql);
      }
      */

      $this->index = $this->alter = array();

      mcms::db()->commit();
    }

    public function getSql()
    {
      if (empty($this->alter))
        return null;

      $sql = mcms::db()->getSql($this->name, $this->alter, $this->isnew);
      return $sql;
    }

    public function delete()
    {
      if (!$this->exists())
        throw new RuntimeException(t('Попытка удалить несуществующую таблицу.'));

      if (in_array($this->name, array('node', 'node__rel', 'node__rev')))
        throw new RuntimeException(t('Попытка удалить жизненно важную таблицу.'));

      mcms::db()->exec("DROP TABLE `{$this->name}`");
    }
};
