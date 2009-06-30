<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class Database extends PDO
{
  protected $dbname = null;
  protected $dbtype = null;

  private $prepared_queries = array();

  private $transaction = false;

  /**
   * Префикс для имени таблицы.  Используется только если не пуст.
   */
  private $prefix = null;

  public function __construct($dsn, $user, $pass)
  {
    parent::__construct($dsn, $user, $pass);

    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (version_compare(PHP_VERSION, "5.1.3", ">="))
      $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
  }

  public function getDbType()
  {
    if (null !== $this->dbtype)
      return $this->dbtype;
    throw new RuntimeException(t('Метод getDbType() не определён '
      .'— используется неполноценный драйвер БД.'));
  }

  public function getDbName()
  {
    return $this->dbname;
  }

  public function getDbFile()
  {
    return null;
  }

  /**
   * Подключение к БД.
   */
  public static function connect(array $dsn)
  {
    if (!isset($dsn['type']))
      throw new NotConnectedException(t('Не указан тип БД.'));

    if (!in_array($dsn['type'], self::listDrivers()))
      throw new NotConnectedException(t('Драйвер для подключения к %scheme отсутствует.', array(
        '%scheme' => $dsn['type'],
        )));

    if (!class_exists($driver = 'mcms_'. $dsn['type'] .'_driver'))
      throw new NotConnectedException(t('Molinos CMS не поддерживает работу с БД типа %name.', array(
        '%name' => $dsn['type'],
        )));

    $db = new $driver($dsn);
    if (array_key_exists('prefix', $dsn))
      $db->prefix = $dsn['prefix'];

    return $db;
  }

  public function prepare($sql)
  {
    $sql = $this->rewriteSQL($sql);
    if (!$this->transaction and $this->isModifying($sql))
      throw new RuntimeException(t('Модификация данных вне транзакции.'));
    $sth = parent::prepare($sql);
    return $sth;
  }

  public function exec($sql, array $params = null)
  {
    if (!is_string($sql))
      throw new InvalidArgumentException(t('SQL запрос должен быть строкой.'));
    if ($sth = $this->prepare($sql))
      $sth->execute($params);
    else
      throw new RuntimeException(t('Не удалось приготовить запрос.'));
    return $sth;
  }

  public function fetch($sql, array $params = null)
  {
    $res = $this->getResults($sql, $params);

    while (is_array($res) and count($res) == 1)
      $res = array_shift($res);

    if (array() === $res)
      $res = null;

    return $res;
  }

  // Возвращает результат запроса в виде ассоциативного массива k => v.
  public function getResultsKV($key, $val, $sql, array $params = null)
  {
    $result = array();
    $rows  = $this->getResults($sql, $params);

    if ($rows) {
      $frow = $rows[0];
      if (!array_key_exists($key,$frow))
         $key = '`'.$key.'`';

      if (!array_key_exists($key,$frow))
         throw new RuntimeException(t("Для запроса %sql в возвращаемом массиве"
            ."нет поля %key", array('%sql' => $sql, '%key' => $key)));

      if (!array_key_exists($val,$frow))
         $val = '`'.$val.'`';

      if (!array_key_exists($val,$frow))
         throw new RuntimeException(t("Для запроса %sql в возвращаемом массиве"
            ."нет поля %key", array('%sql' => $sql, '%key' => $val)));

      foreach ($rows as $row)
        $result[$row[$key]] = $row[$val];
    }

    return $result;
  }

  public function getResultsK($key, $sql, array $params = null)
  {
    $result = array();
    $rows  = $this->getResults($sql, $params);
    if ($rows) {
      if (!array_key_exists($key,$rows[0]))
         $key = '`'.$key.'`';

      if (!array_key_exists($key,$rows[0]))
         throw new RuntimeException(t("Для запроса %sql в возвращаемом массиве"
            ."нет поля %key", array('%sql' => $sql, '%key' => $key)));

      foreach ($rows as $row)
        $result[$row[$key]] = $row;
    }

    return $result;
  }

  public function getResultsV($key, $sql, array $params = null)
  {
    if (!is_string($sql))
      throw new InvalidArgumentException(t('Неверная параметризация getResultsV(): забыл указать имя поля?'));

    $result = array();
    $rows = $this->getResults($sql, $params);
    if ($rows) {
      if (!array_key_exists($key,$rows[0]))
         $key = '`'.$key.'`';

      if (!array_key_exists($key,$rows[0]))
         throw new RuntimeException(t("Для запроса %sql в возвращаемом массиве"
            ."нет поля %key", array('%sql' => $sql, '%key' => $key)));

      foreach ($rows as $row)
        $result[] = $row[$key];
    }

    return empty($result) ? null : $result;
  }

  // Возвращает результат запроса в виде массива.
  public function getResults($sql, array $params = null)
  {
    $sth = $this->exec($sql, $params);
    return $sth->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getResult($sql, array $params = null)
  {
    $data = $this->getResults($sql, $params);

    if (empty($data))
      return null;

    $data = $data[0];

    if (count($data) > 1)
      return $data;
    else
      return array_pop($data);
  }

  // Открываем транзакцию, запоминаем статус.
  public function beginTransaction($reentrant = false)
  {
    if (!$this->transaction) {
      parent::beginTransaction();
      $this->transaction = true;
    } elseif (!$reentrant) {
      // throw new InvalidArgumentException("transaction is already running");
    }
  }

  public function isTransactionRunning()
  {
    return !empty($this->transaction);
  }

  // Откатываем транзакцию, если открыта.
  public function rollback()
  {
    if ($this->transaction) {
      parent::rollback();
      $this->transaction = false;
    }
  }

  // Коммитим транзакцию, если открыта.
  public function commit()
  {
    if ($this->transaction) {
      parent::commit();
      $this->transaction = false;
    }
  }

  public function hasOrderedUpdates()
  {
    return false;
  }

  /**
   * Проеряет, модифицирует ли запрос данные.
   */
  private function isModifying($sql)
  {
    return preg_match(
      '@^(INSERT\s+INTO|REPLACE|UPDATE|DELETE)@',
      strtoupper($sql)
      );
  }

  public static function listDrivers()
  {
    return array_diff(
      PDO::getAvailableDrivers(),
      mcms::config('runtime.db.drivers.disable', array()));
  }

  /**
   * Модификация текста SQL запроса.  Добавляет префикс,
   * может использоваться для других вещей.
   */
  protected function rewriteSQL($sql)
  {
    return str_replace(array('{', '}'), array('`' . $this->prefix, '`'), $sql);
  }

  /**
   * Выполняет вызов в рамках транзакции.
   */
  public function execSecure(array $callee, array $params)
  {
    $this->beginTransaction();

    try {
      call_user_func_array($callee, $params);
      $this->commit();
    } catch (Exception $e) {
      $this->rollback();
      throw $e;
    }
  }
}
