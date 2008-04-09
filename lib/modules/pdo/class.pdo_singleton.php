<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PDO_Singleton extends PDO
{
    static private $instance = null;

    private $prepared_queries = array();
    private $query_log = null;

    private $transaction = false;

    public function __construct()
    {
      $uri = parse_url(mcms::config('dsn'));

      $dsn = $uri['scheme'] .':dbname='. trim($uri['path'], '/') .';host='. $uri['host'];

      parent::__construct($dsn, $uri['user'], $uri['pass']);

      $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, 1);

      if (version_compare(PHP_VERSION, "5.1.3", ">="))
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

      if ((!empty($_GET['profile']) or !empty($_GET['postprofile'])) and bebop_is_debugger())
        $this->query_log = array();

      $this->exec("SET NAMES utf8");
      $this->exec("SET sql_mode = 'STRICT_TRANS_TABLES'");
    }

    public static function getInstance($name = null)
    {
        if (self::$instance === null)
            self::$instance = new PDO_Singleton();
        return self::$instance;
    }

    public static function disconnect()
    {
      if (null !== self::$instance)
        self::$instance = null;
    }

    public function prepare($sql)
    {
        $hash = hash('crc32', $sql);

        if (!array_key_exists($hash, $this->prepared_queries)) {
            $this->prepared_queries[$hash] = parent::prepare($sql);
        }

        $sth = $this->prepared_queries[$hash];

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
        throw new McmsPDOException($e, $sql);
      }

      return $sth;
    }

    public function log($string)
    {
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
      $sth = $this->prepare($sql);
      $sth->execute($params);
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
}
