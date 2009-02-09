<?php
/**
 * Инсталлер Molinos CMS.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author dliv27 <dliv27@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Инсталлер Molinos CMS.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class Installer
{
  /**
   * Создание таблиц.
   *
   * Создаёт базовые таблицы.
   *
   * @todo заменить на автосоздание, форсировать запуск с помощью «SELECT 1
   * FROM...»
   *
   * @return void
   */
  public static function CreateTables()
  {
    $db = Context::last()->db;
    $db->clearDB();

    $t = new TableInfo($db, 'node');

    if (!$t->exists()) {
      $t->columnSet('id', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('lang', array(
        'type' => 'char(4)',
        'required' => true,
        'key' => 'mul',
        ));
      $t->columnSet('rid', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul',
        ));
      $t->columnSet('parent_id', array(
        'type' => 'int',
        'required' => 0,
        ));
      $t->columnSet('class', array(
        'type' => 'varchar(16)',
        'required' => 1,
        'key' => 'mul'
        ));

      $t->columnSet('left', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        ));
      $t->columnSet('right', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
       ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('created', array(
        'type' => 'datetime',
        'required' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('updated', array(
        'type' => 'datetime',
        'required' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('published', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        'key' => 'mul'
        ));
     $t->columnSet('deleted', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        'key' => 'mul'
        ));
      $t->commit();
    }

    $t = new TableInfo($db, 'node__rel');
    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('tid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('key', array(
        'type' => 'varchar(255)',
        'required' => 0,
        'key' =>'mul'
        ));
      $t->columnSet('order', array(
        'type' => 'int',
        'required' => 0,
        'key' =>'mul'
        ));
       $t->commit();
    }

    $t = new TableInfo($db, 'node__access');
    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul',
        ));
      $t->columnSet('uid', array(
        'type' => 'int',
        'required' => 1,
        'key' => 'mul'
        ));
      $t->columnSet('c', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
      $t->columnSet('r', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
      $t->columnSet('u', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
      $t->columnSet('d', array(
        'type' => 'tinyint(1)',
        'required' => 1,
        'default' => 0,
        ));
       $t->commit();
    }
  }

  /**
   * Создание конфигурационного файла.
   *
   * Создаёт новый конфигурационный файл, сохраняет в него настройки.
   *
   * @param array $data данные формы.
   *
   * @param string $olddsn имя ранее использовавшегося DSN (используется при
   * апгрейде БД, на сколько я понимаю — hex).
   *
   * @return void
   */
  public static function writeConfig(array $data, $olddsn = null)
  {
    if (empty($data['confirm']))
      throw new InvalidArgumentException("Вы не подтвердили свои намерения.");

    $config = Context::last()->config;

    if ($olddsn)
      $config->set('default_backup', $olddsn, 'db');

    switch ($data['db']['type']) {
    case 'sqlite':
      if (empty($data['db']['name']))
        $data['db']['name'] = 'default.db';
      elseif (substr($data['db']['name'], -3) != '.db')
        $data['db']['name'] .= '.db';
      $config->set('default', 'sqlite:conf/'. $data['db']['name'], 'db');
      break;

    case 'mysql':
      $config->set('default', sprintf('mysql://%s:%s@%s/%s', $data['db']['user'], $data['db']['pass'], $data['db']['host'], $data['db']['name']), 'db');
      break;
    }

    foreach (self::getDefaults() as $k => $v) {
      $value = array_key_exists($k, $data['config']) ? $data['config'][$k] : $v;
      $config->set($k, $value);
    }

    $config->write();
  }

  private static function getDefaults()
  {
    return array(
      'mail_from' => 'no-reply@cms.molinos.ru',
      'mail_server' => 'localhost',
      'backtracerecipients' => 'cms-bugs@molinos.ru',
      'debuggers' => '127.0.0.1',
      'filestorage' => 'storage',
      'tmpdir' => 'tmp',
      'file_cache_ttl' => 3600,
      );
  }
}
