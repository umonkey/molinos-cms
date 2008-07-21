<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PDO_Singleton extends PDO
{
  static private $instances = array();

  protected $dbname = null;
  protected $dbtype = null;

  private $prepared_queries = array();
  private $query_log = null;

  private $transaction = false;

  public function __construct($dsn, $user, $pass)
  {
    parent::__construct($dsn, $user, $pass);

    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (version_compare(PHP_VERSION, "5.1.3", ">="))
      $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    if ((!empty($_GET['profile']) or !empty($_GET['postprofile'])) and bebop_is_debugger())
      $this->query_log = array();
  }

  public function getDbType()
  {
    if (null !== $this->dbtype)
      return $this->dbtype;
    throw new RuntimeException(t('Метод getDbType() не определён — используется неполноценный драйвер БД.'));
  }

  public function getDbName()
  {
    return $this->dbname;
  }

  public static function getInstance($name, $reload = false)
  {
    if (!array_key_exists($name, self::$instances) or $reload) {
      if (false === ($conf = parse_url(self::getConfig($name))) or empty($conf['scheme']))
        throw new RuntimeException(t('Соединение %name настроено неверно.',
          array('%name' => $name)));

      if (!in_array($conf['scheme'], PDO::getAvailableDrivers())) {
        throw new RuntimeException(t('Указанный в настройках драйвер PDO '
          .'(%name) недоступен. Вероятно конфигурация сервера изменилась '
          .'после установки CMS, или кто-то руками копался в '
          .'конфигурационном файле.', array('%name' => $conf['scheme'])));
      }

      if (!class_exists($driver = 'mcms_'. $conf['scheme'] .'_driver'))
        throw new RuntimeException(t('Драйвер для доступа к БД типа "%name" '
          .'отсутствует.', array('%name' => $conf['scheme'])));

      self::$instances[$name] = new $driver($conf);
    }

    return self::$instances[$name];
  }

  // Возвращает параметры подключения к нужной БД.
  private static function getConfig($name)
  {
    if (is_array($conf = mcms::config('db')) and array_key_exists($name, $conf))
      return $conf[$name];

    if ('default' == $name and is_string($conf = mcms::config('db')))
      return $conf;

    if ('default' == $name)
      throw new NotInstalledException('dsn');

    throw new RuntimeException(t('Соединение %name не настроено.', array('%name' => $name)));
  }

  public static function disconnect()
  {
    /*
    // FIXME: это сработает только если нет ссылок, что не факт.
    // Предлагаю забить, hex, 2008-04-23.
    if (null !== self::$instance)
      self::$instance = null;
    */
  }

  public function prepare($sql)
  {
    $sth = parent::prepare($sql);

    if ($this->query_log !== null)
      $this->query_log[] = $sql;

    return $sth;
  }

  public function exec($sql, array $params = null)
  {
    try {
      $sth = $this->prepare($sql);
      $sth->execute($params);
    } catch (PDOException $e) {
      if ('sqlite' == self::$dbtype) {
        $info = $this->errorInfo();
        $errorcode = $info[1];

        switch ($errorcode) {
        case 1: // General error: 1 no such table
          throw new TableNotFoundException($e);
        }
      } else if ('42S02' == $e->getCode()) {
        throw new TableNotFoundException($e);
      }

      throw new McmsPDOException($e, $sql);
    }

    return $sth;
  }

  public function fetch($sql, array $params = null)
  {
    $res = $this->GetResults($sql, $params);

    while (is_array($res) and count($res) == 1)
      $res = array_shift($res);

    if (array() === $res)
      $res = null;

    return $res;
  }

  public function log($string)
  {
    if (null !== $this->query_log)
      $this->query_log[] = $string;
  }

  // Возвращает результат запроса в виде ассоциативного массива k => v.
  public function getResultsKV($key, $val, $sql, array $params = null)
  {
    $result = array();

    foreach ($this->getResults($sql, $params) as $row)
      $result[$row[$key]] = $row[$val];

    return $result;
  }

  public function getResultsK($key, $sql, array $params = null)
  {
    $result = array();

    foreach ($this->getResults($sql, $params) as $row)
      $result[$row[$key]] = $row;

    return $result;
  }

  public function getResultsV($key, $sql, array $params = null)
  {
    $result = array();

    foreach ($this->getResults($sql, $params) as $row)
      $result[] = $row[$key];

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

  // Возвращает текущий лог запросов.
  public function getLog()
  {
    return $this->query_log;
  }

  // Возвращает количество запросов.
  public function getLogSize()
  {
    $count = 0;

    if (is_array($this->query_log))
      foreach ($this->query_log as $entry)
        if (substr($entry, 0, 2) !== '--')
          $count++;

    return $count;
  }

  // Открываем транзакцию, запоминаем статус.
  public function beginTransaction()
  {
    if (!$this->transaction) {
      parent::beginTransaction();
      $this->transaction = true;
    } else {
      throw new InvalidArgumentException("transaction is already running");
    }
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
}
