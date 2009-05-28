<?php
// vim: expandtab tabstop=4 shiftwidth=4 softtabstop=4:

class TableInfo
{
    protected $isnew;
    protected $name;
    protected $columns;
    protected $alter;
    protected $db;

    public function __construct(PDO_Singleton $db, $name)
    {
      $this->db = $db;
      $this->name = $name;
      $this->coldel = $this->index = $this->columns = $this->alter = array();
      $this->isnew = false;
      $info = $this->scan($name);
    }

    // Получение информации о таблице.
    protected function scan($name)
    {
      $this->oldcolumns = $this->columns = $this->db->getTableInfo($name);

      if (!is_array($this->columns)){
         $this->isnew = true;
         $this->columns = array();
         $this->cur_indexes = array();
      }
      else {
        foreach ($this->columns as $k => $el) {
          $this->cur_indexes[$k] = $el['key'];
        }
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
         $this->addSql($name, $spec, $modify);
      }

      //Вынесли из if. В addsql всё равно не попали, зато
      //гарантированно существующее поле попадает в columns.
      //это важно при удалении полей (при процедуре сравнения).
      $this->columns[$name] = $spec;
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

    public function columnDel($colname)
    {
      if ($this->columnExists($colname)) {
        if ($this->db->getDbType()=='MySQL') { //для SQLite поля удаляются в функции recreateTable
            $this->db->dropColumn($this->name, $colname);
        }
        unset($this->columns[$colname]);
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
      list($sql, $ix) = $this->db->addSql($name, $spec, $modify, $this->isnew);

      $this->alter[] = $sql;

      if ($ix)
        $this->index[] = $ix;
    }

    public function commit()
    {
      $tblname = $this->name;

      if (($this->db->getDbType() == 'SQLite') && !$this->isnew) {
        // Для существующих в SQLite таблиц в случе их модификации убиваем их и создаём с новыми полями,
        // старые значения при этом сохраняются
        $this->db->recreateTable($tblname, $this->columns, $this->oldcolumns);
      }
      else {
        if ((null !== ($sql = $this->getSql()))) {
          $this->db->exec($sql);
        }

        for ($i = 0; $i < count($this->index); $i++) {
          $el = $this->index[$i];

          if (empty($this->cur_indexes[$el])) {
            $sql = " CREATE INDEX `IDX_{$tblname}_{$el}` on `{$tblname}` (`{$el}`)";
            $this->db->exec($sql);
          }
        }
      }

      $this->index = $this->alter = array();

      $this->db->commit();
    }

    public function getSql()
    {
      if (empty($this->alter))
        return null;

      $sql = $this->db->getSql($this->name, $this->alter, $this->isnew);

      return trim($sql);
    }

    public function delete()
    {
      if (!$this->exists())
        throw new RuntimeException(t('Попытка удалить несуществующую таблицу.'));

      if (in_array($this->name, array('node', 'node__rel')))
        throw new RuntimeException(t('Попытка удалить жизненно важную таблицу.'));

      $this->db->exec("DROP TABLE `{$this->name}`");
    }

  /**
   * Возвращает имя таблицы.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Проверяет структуру таблицы.
   */
  public static function check($tableName, array $columns)
  {
    $t = new TableInfo(Context::last()->db, $tableName);
    foreach ($columns as $k => $v)
      $t->columnSet($k, $v);
    $t->commit();
  }
};
