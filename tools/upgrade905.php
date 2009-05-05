<?php

class MigrationAssistant
{
  private $ctx;
  private $db;

  private $path;
  private $site;
  private $conf;
  private $scheme;

  private $reg;

  // Шкуры, которые предстоит скопировать.
  private $themes = array();

  public function __construct()
  {
    chdir($this->path = dirname(dirname(realpath(__FILE__))));

    if (!is_dir($this->site = 'sites' . DIRECTORY_SEPARATOR . 'default'))
      mkdir($this->site, 0750, true);

    require 'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
      . 'core' . DIRECTORY_SEPARATOR . 'class.loader.php';

    Loader::setup();

    $this->ctx = new Context();

    $this->reg = new Registry();
    if (!$this->reg->load())
      $this->reg->rebuild();
  }

  public function __destruct()
  {
    if (!empty($this->themes))
      $this->log('Don\'t forget to copy these themes: ' . join(', ', $this->themes) . '.');
    $this->log('Bye.');
  }

  public function run(array $args)
  {
    try {
      $this->prepare($args);
      $this->process();
      $this->cleanup();
    } catch (Exception $e) {
      mcms::fatal($e);
      $this->log("Error: " . rtrim($e->getMessage(), '.'));
      exit(1);
    }
  }

  // Подготовка к миграции, проверка окружения.
  private function prepare(array $args)
  {
    $this->conf = $this->getConf(isset($args[1]) ? $args[1] : 'conf' . DIRECTORY_SEPARATOR . 'default.config.php');

    if (isset($this->conf['filestorage']) and !is_dir($path = $this->conf['filestorage']))
      throw new Exception($path . ': not found');

    if (!isset($this->conf['db.default']))
      throw new Exception('DSN not set (db.default)');
    else
      $this->db = $this->connect($this->conf['db.default']);

    $this->log('everything looks good, let\'s go.');
  }

  // Загрузка и проверка валидности конфигурационного файла.
  private function getConf($fileName)
  {
    if (!is_readable($fileName))
      throw new Exception($fileName . ': not found');

    if (!is_array($conf = @include $fileName))
      throw new Exception($fileName . ': not a PHP array');

    $this->log($fileName . " looks good.");

    return $conf;
  }

  // Подключение к БД, сохраняется в $this->db.
  private function connect($dsn)
  {
    $parts = parse_url($dsn);
    if (!isset($parts['scheme']))
      throw new Exception('bad DSN: ' . $dsn);

    switch ($this->scheme = $parts['scheme']) {
    case 'sqlite':
      if (!is_readable($parts['path']))
        throw new Exception($parts['path'] . ': not readable');
      if (!is_writable(dirname($parts['path'])))
        throw new Exception(dirname($parts['path']) . ': not readable');

      $target = $this->site . DIRECTORY_SEPARATOR . 'database.sqlite';
      if (file_exists($target))
        unlink($target);
      copy($parts['path'], $target);
      $dsn = 'sqlite:' . $target;
      $this->log('new dsn: '. $dsn);

      $this->conf['db.default'] = 'sqlite:database.sqlite';
      break;
    }

    $db = PDO_Singleton::connect($dsn);

    $this->log("PDO connected.");

    return $db;
  }

  // Все преобразования.
  private function process()
  {
    $this->upgradeTables();
    $this->upgradeFields();
    $this->upgradeIndexes();
    $this->upgradeFiles();
    $this->updateXML();
    $this->upgradeWidgets();
    $this->upgradeDomains();
    $this->writeConfig();
  }

  private function upgradeTables()
  {
    $this->db->beginTransaction();

    $this->log('saving ownership information.');
    $this->db->exec("DELETE FROM `node__rel` WHERE `key` = 'uid'");
    $this->db->exec("INSERT INTO `node__rel` (tid, nid, `key`) SELECT id, uid, 'uid' FROM node WHERE uid IS NOT NULL");

    $this->log('backing up old node table.');
    $this->db->exec("DROP TABLE IF EXISTS `node2`");
    $this->db->exec("ALTER TABLE `node` RENAME TO `node2`");

    $this->log('creating the new node table.');
    $this->db->exec("CREATE TABLE `node` (`id` integer NOT NULL PRIMARY KEY, `lang` char(4) NOT NULL, `parent_id` integer NULL, `class` varchar(16) NOT NULL, `left` integer NULL, `right` integer NULL, `created` datetime NULL, `updated` datetime NULL, `published` tinyint(1) NOT NULL DEFAULT '0', `deleted` tinyint(1) NOT NULL DEFAULT '0', `name` VARCHAR(255) NULL, `name_lc` VARCHAR(255) NULL, `data` MEDIUMBLOB NULL, `xml` MEDIUMBLOB NULL)");

    $this->log('copying nodes.');
    $this->db->exec("INSERT INTO `node` (`id`, `lang`, `parent_id`, `class`, `left`, `right`, `created`, `updated`, `published`, `deleted`, `name`, `name_lc`, `data`) SELECT `n`.`id`, `n`.`lang`, `n`.`parent_id`, `n`.`class`, `n`.`left`, `n`.`right`, `n`.`created`, `n`.`updated`, `n`.`published`, `n`.`deleted`, `v`.`name`, `v`.`name_lc`, `v`.`data` FROM `node2` `n` INNER JOIN `node__rev` `v` ON `v`.`rid` = `n`.`rid`");
    $this->db->exec("DROP TABLE `node2`");

    foreach (array('lang', 'parent_id', 'class', 'left', 'right', 'created', 'updated', 'published', 'deleted', 'name', 'name_lc') as $idx) {
      $this->log('  indexing node.' . $idx);
      $this->db->exec("CREATE INDEX `IDX_node_{$idx}` ON `node` (`{$idx}`)");
    }

    foreach (array('node__rev', 'node__cache', 'node__astat', 'node__seq', 'node__searchindex', 'node__session') as $table) {
      $this->log('  deleting table ' . $table);
      $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
    }

    $this->db->commit();
    $this->log('tables upgraded.');
  }

  private function upgradeWidgets()
  {
    $widgets = array();

    $wsel = $this->db->prepare("SELECT * FROM node WHERE class = 'widget'");
    $wsel->execute();

    $tsel = $this->db->prepare("SELECT name FROM node WHERE class = 'type' AND deleted = 0 AND id IN (SELECT tid FROM node__rel WHERE nid = ?) ORDER BY name");

    while ($row = $wsel->fetch(PDO::FETCH_ASSOC)) {
      $name = $row['name'];
      $data = (array)unserialize($row['data']);

      if (isset($data['config'])) {
        $data = array_merge($data, $data['config']);
        unset($data['config']);
      }

      if (!isset($data['types'])) {
        $types = array();
        $tsel->execute(array($row['id']));
        while ($type = $tsel->fetchColumn(0))
          $types[] = $type;
        $data['types'] = implode(',', $types);
      }

      $data['id'] = $row['id'];

      if (!$row['published'])
        $data['disabled'] = true;

      $widgets[$name] = $data;
    }

    ksort($widgets);

    ini::write($inifile = $this->site . DIRECTORY_SEPARATOR . 'widgets.ini', $widgets);
    $this->log('wrote ' . $inifile);
  }

  // Преобразование доменов из БД в route.ini
  private function upgradeDomains()
  {
    $map = array();
    $this->fetch_domains($map, null, 'GET');

    ksort($map);
    $this->fixDefaultDomain($map);

    ini::write($inifile = $this->site . DIRECTORY_SEPARATOR . 'route.ini', $map);
    $this->log('wrote ' . $inifile);
  }

  private function fetch_domains(array &$map, $parent = null, $prefix = null)
  {
    $where = $parent ? '= ' . intval($parent) : 'IS NULL';
    $sel = $this->db->prepare("SELECT * FROM node WHERE class = 'domain' AND deleted = 0 AND published = 1 AND parent_id {$where} ORDER BY `left`");
    $sel->execute();

    while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
      $row = array_merge($row, (array)@unserialize($row['data']));
      unset($row['data']);

      $key = $prefix . '/' . $row['name'];
      $handler = array();

      foreach (array('title', 'language', 'content_type', 'theme', 'defaultsection') as $k)
        if (isset($row[$k]))
          $handler[$k] = $row[$k];

      $wsel = $this->db->prepare("SELECT name FROM node WHERE class = 'widget' AND deleted = 0 AND id IN (SELECT nid FROM node__rel WHERE tid = ?) ORDER BY name");
      $wsel->execute(array($row['id']));
      for ($widgets = array(); $tmp = $wsel->fetchColumn(0); $widgets[] = $tmp);
      $handler['widgets'] = implode(',', $widgets);

      $handler['call'] = 'BaseRoute::serve';

      if (isset($handler['theme']) and !in_array($handler['theme'], $this->themes))
        $this->themes[] = $handler['theme'];

      switch ($row['params']) {
      case 'doc':
      case 'sec':
        $handler['optional'] = true;
        $map[$key . '/'.'*'] = $handler;
        break;
      case 'sec+doc':
        $handler['optional'] = true;
        $map[$key . '/'.'*'] = $handler;
        $map[$key . '/'.'*'.'/'.'*'] = $handler;
        break;
      default:
        $sfx = $parent ? '' : '/';
        $map[$key . $sfx] = $handler;
      }

      $this->fetch_domains($map, $row['id'], $key);
    }
  }

  private function fixDefaultDomain(array &$map)
  {
    if (empty($map))
      return;

    list($key) = array_keys($map);

    if (count($parts = explode('/', $key)) > 1) {
      $prefix = 'GET/' . $parts[1] . '/';

      foreach ($map as $k => $v) {
        if (0 === strpos($k, $prefix)) {
          $newkey = 'GET/localhost/' . substr($k, strlen($prefix));
          $map[$newkey] = $v;
          unset($map[$k]);
        }
      }
    }
  }

  // Преобразование полей документов.
  private function upgradeFields()
  {
    list($fields, $links) = $this->findAllFields();

    $this->db->beginTransaction();

    $sth = $this->db->prepare("INSERT INTO `node` (`class`, `lang`, `name`, `data`, `published`) VALUES ('field', 'ru', ?, ?, 1)");

    foreach ($fields as $k => $v) {
      // Создаём поле.
      $sth->execute(array($k, serialize($v)));

      $params = array($this->db->lastInsertId());
      $sql = "INSERT INTO `node__rel` (`tid`, `nid`) SELECT `id`, ? FROM `node` WHERE `class` = 'type' AND `name` " . sql::in($links[$k], $params);
      $rel = $this->db->prepare($sql);
      $rel->execute($params);
    }

    $this->fixFieldNames();
    $this->db->commit();

    $this->log(count($fields) . ' doctype fields created.');
  }

  // Обновление индексов.
  private function upgradeIndexes()
  {
    $this->removeOldIndexes();
    $this->createNewIndexes();
  }

  private function removeOldIndexes()
  {
    $this->log("deleting old indexes.");

    $sth = $this->db->prepare("SELECT name FROM node WHERE class = 'type'");
    $sth->execute();

    for ($types = array(); $type = $sth->fetchColumn(0); $types[] = $type);
    unset($sth);

    foreach ($types as $type) {
      $table = 'node__idx_' . $type;
      $this->log('  ' . $table);
      $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
    }
  }

  private function createNewIndexes()
  {
    $this->log("creating new indexes.");
    $this->db->beginTransaction();

    $sth = $this->db->prepare("SELECT name, data FROM node WHERE class = 'field'");
    $sth->execute();

    $types = array();
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      if (!NodeStub::isBasicField($row['name'])) {
        $data = unserialize($row['data']);
        if (!empty($data['indexed']))
          $types[$row['name']] = $data;
      }
    }

    foreach ($types as $name => $info) {
      if (class_exists($info['type'])) {
        $ctl = new $info['type'](array(
          'value' => 'test',
          ) + $info);

        if ($type = $ctl->getSQL()) {
          $table = 'node__idx_' . $name;
          $sql = "CREATE TABLE `{$table}` (`id` INTEGER PRIMARY KEY, `value` {$type} NULL)";

          $this->db->exec($sql);

          $count = 0;
          $sel = $this->db->prepare("SELECT id, data FROM node");
          $upd = $this->db->prepare("INSERT INTO `{$table}` (id, value) VALUES (?, ?)");

          $sel->execute();
          while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($data = @unserialize($row['data']))) {
              if (!empty($data[$name])) {
                $upd->execute(array($row['id'], $data[$name]));
                $count++;
              }
            }
          }

          $this->log(sprintf('  %s  — %s, %u records', $table, $type, $count));
        }
      }
    }

    $this->db->commit();
  }

  private function findAllFields()
  {
    $result = array();
    $links = array();

    $sth = $this->db->prepare("SELECT `name`, `data` FROM `node` WHERE `class` = 'type' AND `deleted` = 0");
    $sth->execute();

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      $type = $row['name'];
      $data = unserialize($row['data']);

      if (!empty($data['fields'])) {
        foreach ($data['fields'] as $k => $v) {
          $v['weight'] = 50;
          if (!isset($result[$k]))
            $result[trim(str_replace('_', '', $k), '_')] = $v;
          $links[$k][] = $type;
        }
      }
    }

    return array($result, $links);
  }

  private function fixFieldNames()
  {
    $count = 0;
    $this->log('removing underscores from field names.');

    $sth = $this->db->prepare("SELECT `id`, `data` FROM `node` WHERE `data` IS NOT NULL");
    $sth->execute();

    $upd = $this->db->prepare("UPDATE `node` SET `data` = ? WHERE `id` = ?");

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      $data = unserialize($row['data']);
      $save = false;

      foreach ($data as $k => $v) {
        $name = trim(str_replace('_', '', $k), '_');

        if ($k != $name) {
          $data[$name] = $v;
          unset($data[$k]);
          $save = true;
        }
      }

      if ($save) {
        $upd->execute(array(serialize($data), $row['id']));
        $count++;
      }
    }

    $this->log("  {$count} nodes updated.");
  }

  // Обновляем файловый архив.
  private function upgradeFiles()
  {
    $this->db->beginTransaction();

    $sel = $this->db->prepare("SELECT id, name, data FROM node WHERE class = 'file'");
    $sel->execute();

    $upd = $this->db->prepare("UPDATE node SET name = ?, data = ? WHERE id = ?");

    $storage = $this->conf['filestorage'];

    $count1 = $count2 = 0;

    while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
      $row = array_merge($row, (array)@unserialize($row['data']));
      $data = unserialize($row['data']);

      // Исправляем случайно закравшийся мусор в виде урлов.
      if (strlen($fileName = $row['filename']) != ($len = strcspn($fileName, '?&')))
        $fileName = $len ? substr($fileName, 0, $len) : 'unnamed';

      $old = os::path($storage, $row['filepath']);
      $new = os::path($storage, $newpath = os::path(dirname($row['filepath']), $fileName = os::getCleanFilename($fileName)));

      // reverse
      if (false) {
        if (file_exists($new) and !file_exists($old))
          rename($new, $old);
      } else {
        if (file_exists($old) and !file_exists($new)) {
          $this->log('  ' . $row['filepath'] . ' => ' . $newpath);
          $count1++;
          // rename($old, $new);
        } else {
          $count2++;
        }
      }

      $data['filepath'] = $newpath;
      $data['filename'] = $fileName;

      $upd->execute(array($fileName, serialize($data), $row['id']));
    }

    $this->db->commit();

    $this->log(sprintf('%u files renamed, %u not changed.', $count1, $count2));
  }

  // Удаление мусора из БД.
  private function cleanup()
  {
    $this->log('cleaning up.');
    $this->db->beginTransaction();

    // 1. Удаляем ссылки на внутренние поля.
    $this->db->exec("DELETE FROM `node__rel` "
      . "WHERE `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'type' AND `name` IN ('widget', 'file', 'domain', 'type', 'rss')) "
      . "AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'field')");

    // 2. Удаляем поля без ссылок.
    $this->db->exec("DELETE FROM `node` WHERE `class` = 'field' AND `id` NOT IN (SELECT `nid` FROM `node__rel`)");

    $this->db->exec("DELETE FROM node WHERE class = 'widget' OR class = 'domain'");
    $this->db->exec("DELETE FROM node__rel WHERE nid NOT IN (SELECT id FROM node)");
    $this->db->exec("DELETE FROM node__rel WHERE tid NOT IN (SELECT id FROM node)");
    $this->db->exec("DELETE FROM node__access WHERE nid NOT IN (SELECT id FROM node)");

    $this->db->commit();

    switch ($this->scheme) {
    case 'sqlite':
      $this->log('vacuum');
      $this->db->exec('VACUUM');
      break;
    }
  }

  private function log($message)
  {
    print rtrim($message) . "\n";
  }

  private function writeConfig()
  {
    $ini = array();
    $ini['db']['dsn'] = $this->conf['db.default'];

    $ini['mail']['from'] = $this->conf['mail.from'];
    $ini['mail']['server'] = $this->conf['mail.server'];

    $ini['debug']['backtrace'] = join(',', $this->conf['backtracerecipients']);
    $ini['debug']['allow'] = join(',', $this->conf['debuggers']);

    $ini['attachment']['storage'] = $this->conf['filestorage'];
    $ini['attachment']['ftp'] = $this->conf['ftp'];

    $ini['core']['tmpdir'] = $this->conf['tmpdir'];

    ini::write($fileName = os::path($this->site, 'config.ini'), $ini);
    $this->log('wrote ' . $fileName);
  }

  private function updateXML()
  {
    $this->log('updating node.xml');

    $this->db->beginTransaction();

    $sel = $this->db->prepare("SELECT id, lang, parent_id, class, created, updated, published, deleted, name, data FROM node WHERE xml IS NULL");
    $sel->execute();

    $ctx = new Context();

    for ($count = 0; $row = $sel->fetch(PDO::FETCH_ASSOC); $count++) {
      $src = $row;
      if (!empty($row['data']))
        if (is_array($data = unserialize($row['data'])))
          $row = array_merge($row, $data);
      unset($row['data']);

      NodeStub::create($row['id'], $this->db, $row)->refresh()->save();
    }

    $this->db->commit();
    $this->log(sprintf('  %u nodes updated.', $count));
  }
}

$ma = new MigrationAssistant();
$ma->run($argv);
