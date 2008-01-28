<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UpdateManager implements iScheduler
{
  private $rel = null;

  public function __construct()
  {
    $this->rel = str_replace(strrchr(BEBOP_VERSION, '.'), '', BEBOP_VERSION);
  }

  // Возвращает ссылку на файл с информацией о последнем билде конкретного релиза.
  public function getReleaseInfoPath()
  {
    return "http://cms.molinos.ru/versioninfo/{$this->rel}.xml";
  }

  // Выкачивает XML файл и записывает в массив информацию о текущем релизе.
  private function parseVersionXML(&$result)
  {
    $result = array(
      'release' => $this->rel,
      'current_build' => substr(strrchr(BEBOP_VERSION, '.'), 1),
      'latest_build' => substr(strrchr(BEBOP_VERSION, '.'), 1),
      'download_url' => null,
      );

    if (null !== ($tmp = mcms_fetch_file($this->getReleaseInfoPath(), false, false))) {
      $xml = simplexml_load_file($tmp);

      foreach ($xml->attributes() as $k => $v) {
        if ($k == 'version' and $v == $this->rel) {
          $attrs = (array)$xml->build[0]->attributes();

          if (!empty($attrs['@attributes']['id']))
            $result['latest_build'] = $attrs['@attributes']['id'];

          if (!empty($attrs['@attributes']['url']))
            $result['download_url'] = $attrs['@attributes']['url'];
        }
      }
    }
  }

  // Возвращает последнюю доступную версию.  Имеет тот же формат, что и BEBOP_VERSION,
  // то есть проверить, последняя ли у нас версия, можно сравнив результат с этой константой.
  public function getVersionInfo($force = false)
  {
    $result = null;
    $cache = BebopCache::getInstance();

    if (!empty($_GET['nocache']) and bebop_is_debugger())
      $force = true;

    if (null === $cache or !is_array($result = $cache->versioninfo) or $force)
      $this->parseVersionXML($result);

    if (null !== $cache and is_array($result))
      $cache->versioninfo = $result;

    return $result;
  }

  // Выполняет указанные в массиве действия.
  public function runUpdates(array $options = null)
  {
    if (!ini_get('safe_mode'))
      set_time_limit(0);

    if (in_array('download', $options)) {
      $zip = $this->doDownload();
      $this->doUnpack($zip);
    }

    if (in_array('tables', $options))
      $this->doUpdateSchema();

    if (in_array('types', $options))
      $this->doUpdateTypes(in_array('reindex', $options));

    if (in_array('users', $options))
      $this->doUpdateUsers();

    if (in_array('ui', $options)) {
      $this->doUpdateWidgets();
      $this->doUpdatePages();
    }

    if (in_array('access', $options))
      $this->doFixAccess();
  }

  // Последние штрихи: правим права на объекты (например потому, что при инсталляции
  // типы создаются до групп, и проставить права на типы документов невозможно).
  private function doFixAccess()
  {
    $pdo = PDO_Singleton::getInstance();

    $map = array(
      'type' => 'Schema Managers',
      'group' => 'User Managers',
      'user' => 'User Managers',
      'widget' => 'Developers',
      'domain' => 'Developers',
      );

    foreach ($map as $type => $group) {
      $pdo->exec("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`) "
        ."SELECT `n`.`id`, `gn`.`id`, 0, 1, 1, 1 "
        ."FROM `node` `n`, `node` `gn`, `node__rev` `r`, `node_group` `g` "
        ."WHERE `n`.`class` = :type AND `gn`.`class` = 'group' AND `gn`.`rid` = `r`.`rid` "
        ."AND `g`.`rid` = `r`.`rid` AND `g`.`login` = :group", array(':type' => $type, ':group' => $group));
    }
  }

  private function doDownload()
  {
    $info = $this->getVersionInfo(true);

    if (null === $info['download_url'])
      throw new InvalidArgumentException(t("Не удалось получить адрес дистрибутива."));

    if (false === ($src = fopen($info['download_url'], 'rb')))
      throw new InvalidArgumentException(t("Не удалось скачать файл %url.", array('%url' => $info['download_url'])));

    if (false === ($dst = fopen($zipname = getcwd() . '/tmp/bebop-update.zip', 'wb')))
      throw new InvalidArgumentException(t("Не удалось сохранить дистрибутив в %path.", array('%path' => $zipname)));

    while (!feof($src))
      fwrite($dst, fread($src, 8192));

    fclose($src);
    fclose($dst);

    return $zipname;
  }

  private function doUnpack($zip)
  {
    $f = zip_open($zip);

    while ($entry = zip_read($f)) {
      $path = zip_entry_name($entry);

      // Каталог.
      if (substr($path, -1) == '/') {
        if (!is_dir($path))
          mkdir($path);
      }

      // Обычный файл.
      else {
        // Удаляем существующий.
        if (file_exists($path))
          rename($path, $path .'.old');

        // Создаём новый.
        if (false === ($out = fopen($path, "wb"))) {
          // Не удалось -- возвращаем старый на место.
          rename($path .'.old', $path);
          throw new InvalidArgumentException(t("Не удалось распаковать файл %path", array('%path' => $path)));
        }

        // Размер нового файла.
        $size = zip_entry_filesize($entry);

        // Разворачиваем файл.
        fwrite($out, zip_entry_read($entry, $size), $size);

        // Закрываем.
        fclose($out);

        // Проставим нормальные права.
        chmod($path, 0664);

        // Удалим старую копию.
        if (file_exists($path .'.old'))
          unlink($path .'.old');
      }
    }

    zip_close($f);
  }

  // Обновление схемы.
  private function doUpdateSchema()
  {
    $tables = array(
      'node' => array(
        'columns' => array(
          'id' => array(
            'type' => 'int(10) unsigned',
            'required' => true,
            'indexed' => true,
            ),
          'lang' => array(
            'type' => 'char(4)',
            'required' => true,
            'indexed' => true,
            ),
          'rid' => array(
            'type' => 'int(10) unsigned',
            'indexed' => true,
            ),
          'parent_id' => array(
            'type' => 'int(10) unsigned',
            'reference' => array(
              'table' => 'node',
              'column' => 'id',
              'update' => 'cascade',
              'delete' => 'cascade',
              ),
            'indexed' => true,
            ),
          'class' => array(
            'type' => 'varchar(16)',
            'required' => true,
            'indexed' => true,
            ),
          'code' => array(
            'type' => 'varchar(16)',
            'unique' => true,
            ),
          'left' => array(
            'type' => 'int(10) unsigned',
            'required' => true,
            'unique' => true,
            ),
          'right' => array(
            'type' => 'int(10) unsigned',
            'required' => true,
            'unique' => true,
            ),
          'uid' => array(
            'type' => 'int(10) unsigned',
            'indexed' => true,
            ),
          'created' => array(
            'type' => 'datetime',
            'indexed' => true,
            ),
          'updated' => array(
            'type' => 'datetime',
            'indexed' => true,
            ),
          'published' => array(
            'type' => 'tinyint(1)',
            'indexed' => true,
            'required' => true,
            'default' => '0',
            ),
          'deleted' => array(
            'type' => 'tinyint(1)',
            'indexed' => true,
            'required' => true,
            'default' => '0',
            ),
          ),
        ),
        // node revisions
        'node__rev' => array(
          'columns' => array(
            'rid' => array(
              'type' => 'int(10) unsigned',
              'pk' => true,
              'autoincrement' => true,
              'required' => true,
              ),
            'nid' => array(
              'type' => 'int(10) unsigned',
              'indexed' => true,
              ),
            'uid' => array(
              'type' => 'int(10) unsigned',
              'indexed' => true,
              ),
            'name' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              ),
            'data' => array(
              'type' => 'mediumblob',
              ),
            'created' => array(
              'type' => 'datetime',
              'required' => true,
              'indexed' => true,
              ),
            'html' => array(
              'type' => 'blob',
              ),
            ),
          ),
        'node_type' => array(
          'columns' => array(
            'rid' => array(
              'type' => 'int(10) unsigned',
              'reference' => array(
                'table' => 'node',
                'column' => 'rid',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              'pk' => true,
              'required' => true,
              ),
            'title' => array(
              'type' => 'varchar(255)',
              'required' => true,
              'indexed' => true,
              ),
            'hidden' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              'indexed' => true,
              ),
            'internal' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              'indexed' => true,
              ),
            'notags' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              'indexed' => true,
              ),
            'nodrafts' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              'indexed' => true,
              ),
            'nopreview' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              'indexed' => true,
              ),
            'sendmail' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              'indexed' => true,
              ),
            ),
          ),
        'node_file' => array(
          'columns' => array(
            'rid' => array(
              'type' => 'int(10) unsigned',
              'reference' => array(
                'table' => 'node',
                'column' => 'rid',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              'pk' => true,
              ),
            'filetype' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              'required' => true,
              ),
            'filepath' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              'required' => true,
              ),
            'filename' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              'required' => true,
              ),
            'filesize' => array(
              'type' => 'int(10) unsigned',
              'indexed' => true,
              'required' => true,
              'default' => '0',
              ),
            ),
          ),
        'node_group' => array(
          'columns' => array(
            'rid' => array(
              'type' => 'int(10) unsigned',
              'reference' => array(
                'table' => 'node',
                'column' => 'rid',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              'pk' => true,
              ),
            'login' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              'required' => true,
              ),
            'system' => array(
              'type' => 'tinyint(1)',
              'indexed' => true,
              'required' => true,
              'default' => '0',
              ),
            ),
          ),
        'node_user' => array(
          'columns' => array(
            'rid' => array(
              'type' => 'int(10) unsigned',
              'reference' => array(
                'table' => 'node',
                'column' => 'rid',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              'pk' => true,
              ),
            'login' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              'required' => true,
              ),
            'password' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              ),
            'email' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              ),
            'publisher' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              ),
            'system' => array(
              'type' => 'tinyint(1)',
              'indexed' => true,
              'required' => true,
              'default' => '0',
              ),
            ),
          ),
        'node__rel' => array(
          'columns' => array(
            'nid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'reference' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              'indexed' => true,
              ),
            'tid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'reference' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              'indexed' => true,
              ),
            'key' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              ),
            'order' => array(
              'type' => 'int(10) unsigned',
              'indexed' => true,
              ),
            ),
          'keys' => array(
            'typical1' => array(
              'type' => 'unique',
              'columns' => array('nid', 'tid', 'key'),
              ),
            ),
          ),
        'node__log' => array(
          'columns' => array(
            'lid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'autoincrement' => true,
              'pk' => true,
              ),
            'nid' => array(
              'type' => 'int(10) unsigned',
              'indexed' => true,
              ),
            'uid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              ),
            'username' => array(
              'type' => 'varchar(255)',
              'required' => true,
              ),
            'ip' => array(
              'type' => 'varchar(16)',
              'required' => true,
              ),
            'operation' => array(
              'type' => 'enum(\'create\',\'update\',\'delete\',\'undelete\',\'erase\',\'publish\',\'unpublish\',\'search\',\'cron\')',
              'required' => true,
              ),
            'timestamp' => array(
              'type' => 'datetime',
              'required' => true,
              ),
            'query' => array(
              'type' => 'varchar(255)',
              'indexed' => true,
              ),
            ),
          ),
        'node__cache' => array(
          'columns' => array(
            'cid' => array(
              'type' => 'char(32)',
              'required' => true,
              ),
            'lang' => array(
              'type' => 'char(2)',
              'required' => true,
              ),
            'data' => array(
              'type' => 'mediumblob',
              ),
            ),
          'keys' => array(
            'typical1' => array(
              'type' => 'primary',
              'columns' => array('cid', 'lang'),
              ),
            ),
          ),
        'node__access' => array(
          'columns' => array(
            'nid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'referenc' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              ),
            'uid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'reference' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              ),
            'c' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              ),
            'r' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              ),
            'u' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              ),
            'd' => array(
              'type' => 'tinyint(1)',
              'required' => true,
              'default' => '0',
              ),
            ),
          'keys' => array(
            'typical1' => array(
              'type' => 'primary',
              'columns' => array('nid', 'uid'),
              ),
            ),
          ),
        'node__subscription_emails' => array(
          'columns' => array(
            'id' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'unique' => true,
              'autoincrement' => true,
              'pk' => true,
              ),
            'email' => array(
              'type' => 'varchar(255)',
              'unique' => true,
              ),
            'active' => array(
              'type' => 'tinyint(1)',
              'indexed' => true,
              'required' => true,
              'default' => '0',
              ),
            'digest' => array(
              'type' => 'tinyint(1)',
              'indexed' => true,
              'required' => true,
              'default' => '0',
              ),
            'last' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              ),
            ),
          ),
        'node__subscription_tags' => array(
          'columns' => array(
            'sid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'reference' => array(
                'table' => 'node__subscription_emails',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              ),
            'tid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'reference' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              ),
            ),
          'keys' => array(
            'typical1' => array(
              'type' => 'unique',
              'columns' => array('sid', 'tid'),
              ),
            ),
          ),
        'node__rating' => array(
          'columns' => array(
            'nid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'referenc' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'cascade',
                ),
              ),
            'uid' => array(
              'type' => 'int(10) unsigned',
              'required' => true,
              'indexed' => true,
              'reference' => array(
                'table' => 'node',
                'column' => 'id',
                'update' => 'cascade',
                'delete' => 'null',
                ),
              ),
            'ip' => array(
              'type' => 'varchar(15)',
              'required' => true,
              'indexed' => true,
              ),
            'rate' => array(
              'type' => 'decimal(5,4)',
              'required' => true,
              'indexed' => true,
              ),
            ),
          ),
      );

    foreach ($tables as $k => $v) {
      InfoSchema::getInstance()->checkTable($k, $v);
    }
  }

  // Обновление типов документов.
  private function doUpdateTypes($reindex = false)
  {
    $classes = array(
      'type' => array(
        'title' => 'Тип документа',
        'description' => 'Описание структуры документов.&nbsp; Этого объекта не должно быть здесь видно!',
        'internal' => true,
        'hidden' => true,
        'notags' => false,
        'nodrafts' => true,
        'nopreview' => true,
        'widgets' => array(
          'BebopSchema',
          ),
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Внутреннее имя',
            'description' => "Может содержать только буквы латинского алфавита, арабские цифры и символ подчёркивания («_»).",
            'required' => true,
            'internal' => true,
            ),
          'title' => array(
            'type' => 'TextLineControl',
            'label' => 'Название типа',
            'description' => "Короткое, максимально информативное описание название документа.&nbsp; Хорошо: &laquo;Статья&raquo;, &laquo;Баннер 85x31&raquo;, плохо: &laquo;Текстовый документ для отображения на сайте&raquo;, &laquo;Баннер справа сверху под тем другим баннером&raquo;.",
            'required' => true,
            'internal' => true,
            'indexed' => true,
            ),
          'description' => array(
            'type' => 'TextAreaControl',
            'label' => 'Описание',
            'internal' => true,
            ),
          'notificationrecipients' => array(
            'type' => 'TextLineControl',
            'label' => 'E-mail модератора',
            'description' => 'Один или несколько адресов, на которые будет отправляться сообщение о появлении новых документов этого типа.&nbsp; Разделитель &mdash; запятая.',
            'internal' => true,
            ),
          'internal' => array(
            'type' => 'BoolControl',
            'label' => 'Внутренний',
            'hidden' => true,
            'internal' => true,
            'readonly' => true,
            'indexed' => true,
            'required' => true,
            'default' => '0',
            ),
          'hidden' => array(
            'type' => 'BoolControl',
            'label' => 'Скрытый',
            'internal' => true,
            'readonly' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          'notags' => array(
            'type' => 'BoolControl',
            'label' => 'Не работает с разделами',
            'internal' => true,
            'readonly' => true,
            'indexed' => true,
            ),
          'nodrafts' => array(
            'type' => 'BoolControl',
            'label' => 'Не использовать черновики в работе',
            'description' => "Изменения будут активированы при сохранении (если пользователь не является выпускающим редактором &mdash; будет автоматически отправлено сообщение модератору).&nbsp; Возможность откатиться на предыдущую ревизию останется.",
            'internal' => true,
            'readonly' => false,
            'indexed' => true,
            ),
          'nopreview' => array(
            'type' => 'BoolControl',
            'label' => 'Не использовать предварительный просмотр',
            'description' => "Форма редактирования и создания документа не будет содержать кнопку предварительного просмотра.",
            'internal' => true,
            'readonly' => false,
            'indexed' => true,
            ),
          'hasfiles' => array(
            'type' => 'BoolControl',
            'label' => 'Разрешить прикрепление произвольных файлов',
            'description' => 'Если эта опция включена, при редактировании документа пользователю будет доступна вкладка для добавления произвольных файлов в любом количестве.&nbsp; Без этой опции файлы можно будет добавлять только в специально созданные для этого поля.',
            'internal' => true,
            'readonly' => false,
            'indexed' => false,
            ),
          'sendmail' => array(
            'type' => 'BoolControl',
            'label' => 'Разрешить подписку по почте',
            'internal' => true,
            'indexed' => true,
            ),
          ),
        ),
      'widget' => array(
        'title' => 'Виджет',
        'description' => 'Содержит информацию о виджете.',
        'internal' => true,
        'hidden' => true,
        'notags' => true,
        'nodrafts' => true,
        'nopreview' => true,
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Внутреннее имя',
            'description' => 'Используется для идентификации виджета внутри шаблонов, а также для поиска шаблонов для виджета.',
            'required' => true,
            'internal' => true,
            ),
          'title' => array(
            'type' => 'TextLineControl',
            'label' => 'Название',
            'description' => 'Человеческое название виджета.',
            'required' => true,
            'internal' => true,
            'indexed' => true,
            ),
          'description' => array(
            'type' => 'TextAreaControl',
            'label' => 'Описание',
            'description' => 'Краткое описание выполняемых виджетом функций и особенностей его работы.',
            'required' => false,
            'internal' => true,
            ),
          'classname' => array(
            'type' => 'TextLineControl',
            'label' => 'Используемый класс',
            'required' => true,
            'internal' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          'config' => array(
            'type' => 'HiddenControl',
            'internal' => true,
            ),
          'internal' => array(
            'type' => 'BoolControl',
            'label' => t('Виден только группе CMS Developers'),
            'internal' => true,
            'hidden' => true,
            'required' => true,
            'indexed' => true,
            ),
          ),
        ),
      'domain' => array(
        'title' => 'Домен или урл',
        'description' => 'Описывает домен или урл, используется для формирования структуры сайта (не путать со структурой данных).',
        'internal' => true,
        'hidden' => true,
        'notags' => true,
        'nodrafts' => true,
        'nopreview' => true,
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Имя',
            'description' => 'Имя домена или элемента пути.&nbsp; Для главной страницы это поле должно содержать имя домена.',
            'required' => true,
            'internal' => true,
            ),
          'title' => array(
            'type' => 'TextLineControl',
            'label' => 'Заголовок',
            'description' => 'Используется в качестве заголовка страницы.',
            'required' => true,
            'internal' => true,
            ),
          'description' => array(
            'type' => 'TextAreaControl',
            'label' => 'Описание',
            'description' => 'Описание страницы, используется в тэгах META (для SEO) и в административном интерфейсе.&nbsp; Может использоваться шаблонами для других целей.',
            'internal' => true,
            ),
          'parent_id' => array(
            'type' => 'EnumControl',
            'label' => 'Родительский объект',
            'internal' => true,
            ),
          'aliases' => array(
            'type' => 'TextAreaControl',
            'label' => 'Дополнительные адреса',
            'description' => 'Список дополнительных адресов, на которые откликается этот домен, по одному в строке.',
            'internal' => true,
            ),
          'language' => array(
            'type' => 'EnumControl',
            'label' => 'Язык',
            'description' => 'Язык, применяемый к этому пути, например, &laquo;en&raquo; или &laquo;ru&raquo;.',
            'default' => 'en',
            'required' => true,
            'internal' => true,
            'options' => array(
              'en' => t('Английский (en)'),
              'ru' => t('Русский (ru)'),
              'eo' => t('Эсперанто (eo)'),
              ),
            ),
          'theme' => array(
            'type' => 'TextLineControl',
            'label' => 'Шкура',
            'description' => 'Применяемая к странице тема.',
            'required' => true,
            'internal' => true,
            ),
          'content_type' => array(
            'type' => 'EnumControl',
            'label' => 'Тип контента',
            'description' => 'Обычно здесь пишут &laquo;text/html&raquo;, но иногда нужны другие значения, например, &laquo;text/xml&raquo; для страниц, возвращающих XML.',
            'required' => true,
            'internal' => true,
            'options' => array(
              'text/html' => t('HTML (text/html)'),
              'application/xml' => t('XML (application/xml)'),
              'application/rss+xml' => t('RSS (application/rss+xml)'),
              ),
            'default' => 'text/html',
            ),
          'http_code' => array(
            'type' => 'NumberControl',
            'label' => 'HTTP код',
            'description' => 'Для обычных страниц используется значение 200, другие значения зарезервированы для страниц обработки ошибок.',
            'default' => '200',
            'required' => true,
            'internal' => true,
            'hidden' => true,
          ),
          'html_charset' => array(
            'type' => 'TextLineControl',
            'label' => 'Кодировка результата',
            'default' => 'utf-8',
            'required' => true,
            'internal' => true,
            'hidden' => true,
            ),
          'hidden' => array(
            'type' => 'BoolControl',
            'label' => 'Скрытый',
            'internal' => true,
            'readonly' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          'params' => array(
            'type' => 'EnumControl',
            'label' => 'Разметка параметров',
            'description' => "Выберите порядок дополнительных параметров для этой страницы.&nbsp; Эта настройка используется виджетами для определения запрошенного пользователем раздела и документа.",
            'values' => "sec+doc = /раздел/докумен/\nsec = /раздел/\ndoc = /документ/",
            'default' => 'без параметров',
            'required' => true,
            'internal' => true,
            'indexed' => false,
            ),
          ),
        ),
      'file' => array(
        'title' => 'Файл',
        'description' => 'Этот тип используется для закачки на сайт картинок, архивов и других документов, отображаемых на сайте или скачиваемых пользователем.',
        'internal' => true,
        'notags' => true,
        'nodrafts' => true,
        'nopreview' => true,
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Название файла',
            'description' => 'Человеческое название файла, например: &laquo;Финансовый отчёт за 2007-й год&raquo;',
            'required' => true,
            'internal' => true,
            ),
          'filename' => array(
            'type' => 'TextLineControl',
            'label' => 'Оригинальное имя',
            'description' => 'Имя, которое было у файла, когда пользователь добавлял его на сайт.&nbsp; Под этим же именем файл будет сохранён, если пользователь попытается его сохранить.&nbsp; Рекомендуется использовать только латинский алфавит: Internet Explorer некорректно обрабатывает кириллицу в именах файлов при скачивании файлов.',
            'required' => true,
            'internal' => true,
            'indexed' => true,
            ),
          'filetype' => array(
            'type' => 'TextLineControl',
            'internal' => true,
            'label' => 'Тип MIME',
            'description' => 'Используется для определения способов обработки файла.&nbsp; Проставляется автоматически при закачке.',
            'required' => true,
            'readonly' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          'filesize' => array(
            'type' => 'NumberControl',
            'internal' => true,
            'label' => 'Размер в байтах',
            'required' => true,
            'readonly' => true,
            'indexed' => true,
            'unsigned' => true,
            'default' => 0,
            'hidden' => true,
            ),
          'filepath' => array(
            'type' => 'TextLineControl',
            'internal' => true,
            'label' => 'Локальный путь к файлу',
            'required' => true,
            'readonly' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          'width' => array(
            'type' => 'NumberControl',
            'internal' => true,
            'label' => 'Ширина',
            'description' => 'Проставляется только для картинок и SWF объектов.',
            'readonly' => true,
            'hidden' => true,
            ),
          'height' => array(
            'type' => 'NumberControl',
            'internal' => true,
            'label' => 'Высота',
            'description' => 'Проставляется только для картинок и SWF объектов.',
            'readonly' => true,
            'hidden' => true,
            ),
          ),
        ),
      'tag' => array(
        'title' => 'Раздел сайта',
        'description' => 'Используется для формирования структуры сайта.&nbsp; Использовать этот тип напрямую в наполнении сайта невозможно.&nbsp; Он вообще не должен здесь отображаться.',
        'internal' => true,
        'notags' => true,
        'nodrafts' => true,
        'nopreview' => true,
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Имя раздела',
            'required' => true,
            'internal' => true,
            ),
          'code' => array(
            'type' => 'TextLineControl',
            'label' => 'Внутренний код',
            'description' => 'Может использоваться для доступа к объектам, наравне с числовым идентификатором.&nbsp; Значения должны быть уникальны в пределах всей базы данных.&nbsp; Использовать прямой слэш нельзя (да и обратный не рекомендуется).',
            'internal' => true,
            'length' => 16,
            ),
          'archive' => array(
            'type' => 'BoolControl',
            'label' => 'Использовать навигацию по архиву',
            'description' => 'Позволяет при просмотре содержимого раздела использовать календарь для навигации.',
            'internal' => true,
            ),
          'bebop_subscribe' => array(
            'type' => 'BoolControl',
            'label' => 'Разрешить подписку',
            'description' => 'Разрешить пользователям подписываться на почтовые уведомления об обновлениях в этом разделе.',
            'internal' => true,
            'indexed' => true,
            ),
          ),
        ),
      'user' => array(
        'title' => 'Профиль пользователя',
        'description' => 'Определяет структуру профиля пользователя.',
        'internal' => true,
        'notags' => true,
        'nodrafts' => true,
        'nopreview' => true,
        'widgets' => array('BebopUsers'),
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Имя',
            'description' => 'Полное имя пользователя, используется в приветствиях, почтовых рассылках и других подобных местах.',
            'internal' => true,
            ),
          'login' => array(
            'type' => 'TextLineControl',
            'label' => 'Логин',
            'description' => 'Используется для входа в систему, может состоять только из латинских букв, цифр и пунктуации.',
            'internal' => true,
            'required' => true,
            'indexed' => true,
            ),
          'password' => array(
            'type' => 'PasswordControl',
            'label' => 'Пароль',
            'description' => 'Используется для входа в систему, хранится в зашифрованном виде.&nbsp; Чтобы изменить, введите новое значение.',
            'internal' => true,
            'indexed' => true,
            ),
          'email' => array(
            'type' => 'EmailControl',
            'label' => 'Почтовый адрес',
            'description' => 'На этот адрес пользователь получает уведомления, информацию о восстановлении пароля итд.',
            'internal' => true,
            'indexed' => true,
            ),
          'publisher' => array(
            'type' => 'TextLineControl',
            'label' => 'Выпускающий редактор',
            'description' => 'Введите логин пользователя, являющего выпускающим редактором для текущего.',
            'indexed' => true,
            ),
          'system' => array(
            'type' => 'BoolControl',
            'label' => 'Встроенный пользователь',
            'internal' => true,
            'readonly' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          ),
        ),
      'group' => array(
        'title' => 'Группа пользователей',
        'description' => 'Определяет структуру групп пользователя.',
        'internal' => true,
        'notags' => true,
        'nodrafts' => true,
        'nopreview' => true,
        'widgets' => array('BebopGroups'),
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Имя',
            'description' => 'Название группы, используемое в приветствиях, почтовых рассылках и других подобных местах.',
            'internal' => true,
            ),
          // Сюда копируется name, для форсирования уникальности.
          'login' => array(
            'type' => 'TextLineControl',
            'hidden' => true,
            'internal' => true,
            'indexed' => true,
            'required' => true,
            ),
          'system' => array(
            'type' => 'BoolControl',
            'label' => 'Встроенная группа',
            'internal' => true,
            'readonly' => true,
            'indexed' => true,
            'hidden' => true,
            ),
          ),
        ),
      );

    foreach ($classes as $class => $info) {
      try {
        $type = Node::load(array('class' => 'type', 'name' => $class));
      } catch (ObjectNotFoundException $e) {
        $type = Node::create('type');
        $type->name = $class;
      }

      // Обновляем базовые свойства.
      foreach (array('title', 'description', 'hidden', 'internal', 'notags', 'nodrafts', 'nopreview') as $field) {
        if (array_key_exists($field, $info))
          $type->$field = $info[$field];
      }

      // Обновляем поля.
      foreach ($info['fields'] as $k => $v) {
        $v['internal'] = true;
        $schema = array_merge((array)$type->fieldGet($k), $v);

        if ($schema['type'] == 'BoolControl') {
          $schema['required'] = true;
          $schema['default'] = '0';
        }

        elseif ($schema['type'] == 'HiddenControl')
          $schema['hidden'] = true;

        $type->fieldSet($k, $schema);
      }

      $type->save(false, $type->rid);

      $type->publish($type->rid);
      $type->setAccess(array(
        'Schema Managers' => array('r', 'u', 'd'),
        ), false);
    }

    // Проверяем индексы.
    foreach ($classes as $k => $v)
      Tagger::getInstance()->checkIndexes($k, $reindex);
  }

  // Обновление встроенных пользователей.
  private function doUpdateUsers()
  {
    $pdo = PDO_Singleton::getInstance();

    $groups = array(
      'Access Managers' => 'Менеджеры доступа',
      'Content Managers' => 'Менеджеры контента',
      'Schema Managers' => 'Менеджеры типов документов',
      'Structure Managers' => 'Менеджеры разделов',
      'Developers' => 'Разработчики сайта',
      'User Managers' => 'Менеджеры пользователей',
      'CMS Developers' => 'Разработчики CMS',
      'Subscription Managers' => 'Менеджеры почтовой рассылки',
      'Publishers' => 'Публикаторы',
      'Visitors' => 'Посетители',
      );

    $users = array(
      'root' => array(
        'name' => 'Самый Главный',
        'password' => md5(microtime()),
        'system' => true,
        'groups' => array(
          'Access Managers',
          'Content Managers',
          'Schema Managers',
          'Structure Managers',
          'Developers',
          'User Managers',
          'Subscription Managers',
          'Publishers',
          'Visitors',
          ),
        ),
      'anonymous' => array(
        'name' => 'anonymous',
        'password' => null,
        'system' => true,
        'groups' => array(
          'Visitors',
          ),
        ),
      );

    foreach ($groups as $login => $title) {
      $nodes = Node::find(array('class' => 'group', 'login' => $login));

      if (count($nodes) == 0) {
        $node = Node::create('group');
        $node->login = $login;
        // printf("  creating group '%s'\n", $login);
      } else {
        $node = array_shift($nodes);
      }

      $node->name = $title;
      $node->system = !($login == 'Visitors');
      $node->save(false);

      $node->publish($node->rid);
    }

    foreach ($users as $login => $info) {
      $nodes = Node::find(array('class' => 'user', 'login' => $login));

      if (count($nodes) == 0) {
        $node = Node::create('user');
        $node->login = $login;
        // printf("  creating user '%s'\n", $login);
      } else {
        $node = array_shift($nodes);
        unset($info['password']);
      }

      foreach ($info as $k => $v)
        if ($k !== 'groups')
          $node->$k = $v;

      $node->save();

      $node->publish($node->rid);

      $grouplist = "'". join("', '", $info['groups']) ."'";

      $pdo->exec("DELETE FROM `node__rel` WHERE `tid` IN (SELECT `id` FROM `node` WHERE `class` = 'group') AND `nid` = :uid", array(':uid' => $node->id));
      $pdo->exec("INSERT INTO `node__rel` (`tid`, `nid`) SELECT `n`.`id`, :uid FROM `node` `n` INNER JOIN `node_group` `g` ON `g`.`rid` = `n`.`rid` WHERE `g`.`login` IN ({$grouplist})", array(':uid' => $node->id));

      $node->setAccess(array(
        'Visitors' => array('r'),
        'User Managers' => array('r', 'u', 'd'),
        ), false);
    }
  }

  // Обновление административных виджетов.
  private function doUpdateWidgets()
  {
    // Структура виджетов.
    $widgets = array(
      'BebopDashboard' => array(
        'title' => 'Панель',
        'class' => 'BebopDashboard',
        'internal' => true,
        ),
      'BebopStatus' => array(
        'title' => 'Состояние системы',
        'class' => 'StatusAdminWidget',
        'internal' => true,
        ),
      'BebopUpdates' => array(
        'title' => 'Обновление системы',
        'class' => 'UpdateAdminWidget',
        'internal' => true,
        ),
      'profile' => array(
        'title' => 'Профиль пользователя',
        'class' => 'UserWidget',
        ),
      'BebopStructure' => array(
        'title' => 'Список разделов сайта',
        'class' => 'ListAdminWidget',
        'config' => array(
          'tree' => 'tag',
          'columns' => array(
            'name' => t('Название'),
            'code' => t('Код'),
            'updated' => t('Изменён'),
            'created' => t('Создан'),
            'actions' => t('Действия'),
            ),
          ),
        'internal' => true,
        ),
      'BebopSchema' => array(
        'title' => 'Типы документов',
        'class' => 'ListAdminWidget',
        'config' => array(
          'filter' => array(
            'class' => array(
              'type',
              ),
            'published' => array(0, 1),
            ),
          'selectors' => array(
            'all' => t('все'),
            'none' => t('ни одного'),
            ),
          'columns' => array(
            'title' => t('Название'),
            'name' => t('Внутреннее'),
            'updated' => t('Изменён'),
            'created' => t('Добавлен'),
            ),
          'sortable' => array(
            'name',
            'title',
            'updated',
            'created',
            ),
          'sort' => 'type.title',
          'limit' => 10,
          ),
        'internal' => true,
        ),
      'BebopRecycleBin' => array(
        'title' => 'Список удалённых объектов',
        'class' => 'ListAdminWidget',
        'config' => array(
          'filter' => array(
            'deleted' => 1,
            'published' => array(0, 1),
            ),
          'operations' => array(
            'undelete' => t('Восстановить'),
            'erase' => t('Удалить с концами'),
            ),
          'selectors' => array(
            'all' => t('все'),
            'none' => t('ни одного'),
            ),
          'columns' => array(
            'name' => t('Название'),
            'class' => t('Тип'),
            'uid' => t('Автор'),
            'updated' => t('Изменён'),
            'created' => t('Создан'),
            ),
          'sortable' => array(
            'name',
            'updated',
            'created',
            ),
          'limit' => 10,
          ),
        'internal' => true,
        ),
      'BebopContentList' => array(
        'title' => 'Список документов',
        'class' => 'ListAdminWidget',
        'internal' => true,
        'config' => array(
          'filter' => array(
            'deleted' => 0,
            '-class' => array(
              'user',
              'group',
              'domain',
              'widget',
              'type',
              'tag',
              'file',
              ),
            'published' => array(0, 1),
            ),
          'columns' => array(
            'name' => t('Название'),
            'class' => t('Тип'),
            'uid' => t('Автор'),
            'updated' => t('Изменён'),
            'created' => t('Создан'),
            ),
          'sortable' => array(
            'name',
            'updated',
            'created',
            ),
          'sections' => true,
          'filterform' => '/admin/content/filter/',
          'limit' => 10,
          'sort' => '-id',
          ),
        ),
      'BebopContentFilterSettings' => array(
        'title' => 'Настройка фильтра',
        'class' => 'ContentFilterWidget',
        'internal' => true,
        'config' => array(
          ),
        ),
      'BebopContentCreate' => array(
        'title' => 'Создание документа',
        'class' => 'FormWidget',
        'internal' => true,
        'config' => array(
          'showall' => true,
          'createlabel' => '$type',
          'next' => '/admin/content/',
          ),
        ),
      'BebopWidgets' => array(
        'title' => 'Конструктор',
        'class' => 'ListAdminWidget',
        'internal' => true,
        'config' => array(
          'tree' => 'domain',
          'selectors' => array(
            'all' => t('все'),
            'none' => t('ни одного'),
            ),
          'columns' => array(
            'name' => t('Название'),
            'title' => t('Заголовок'),
            'params' => t('Параметры'),
            'content_type' => t('Тип'),
            'theme' => t('Шкура'),
            'language' => t('Язык'),
            ),
          ),
        ),
      'BebopWidgetList' => array(
        'title' => 'Список виджетов',
        'class' => 'ListAdminWidget',
        'internal' => true,
        'config' => array(
          'filter' => array(
            'class' => array(
              'widget',
              ),
            'internal' => 0,
            'published' => array(0, 1),
            ),
          'columns' => array(
            'name' => 'Имя',
            'title' => 'Название',
            'classname' => 'Тип',
            ),
          'sortable' => array(
            'name',
            'title',
            'type',
            ),
          'limit' => 10,
          ),
        ),
      'BebopFiles' => array(
        'title' => 'Файловый архив',
        'class' => 'ListAdminWidget',
        'internal' => true,
        'config' => array(
          'filter' => array(
            'class' => 'file',
            'deleted' => 0,
            'published' => array(0, 1),
            ),
          'operations' => array(
            'delete' => t('Удалить'),
            'publish' => t('Опубликовать'),
            'unpublish' => t('Скрыть'),
            ),
          'columns' => array(
            'name' => t('Название'),
            'filetype' => t('Тип'),
            'filesize' => t('Размер'),
            'uid' => t('Автор'),
            'updated' => t('Изменён'),
            'created' => t('Добавлен'),
            ),
          'sortable' => array(
            'name',
            'filetype',
            'filesize',
            'updated',
            'created',
            ),
          'limit' => 10,
          ),
        ),
      'BebopFilesRedux' => array(
        'title' => 'Файловый архив (AJAX)',
        'class' => 'ListWidget',
        'internal' => true,
        'config' => array(
          'showall' => true,
          'types' => array('file'),
          'limit' => 20,
          'sort' => array(
            'fields' => array('id'),
            'reverse' => array('id'),
            ),
          ),
        ),
      'BebopNode' => array(
        'title' => 'Свойства документа',
        'class' => 'NodeAdminWidget',
        'internal' => true,
        ),
      'BebopUsers' => array(
        'title' => 'Список пользователей',
        'class' => 'ListAdminWidget',
        'internal' => true,
        'config' => array(
          'filter' => array(
            'class' => array(
              'user',
              ),
            'published' => array(0, 1),
            ),
          'columns' => array(
            'name' => t('Имя'),
            'login' => t('Логин'),
            'email' => t('Почта'),
            'updated' => t('Изменён'),
            'created' => t('Добавлен'),
            ),
          'sortable' => array(
            'name',
            'login',
            'email',
            'updated',
            'created',
            ),
          'limit' => 10,
          ),
        ),
      'BebopGroups' => array(
        'title' => 'Список групп',
        'class' => 'ListAdminWidget',
        'internal' => true,
        'config' => array(
          'filter' => array(
            'class' => array(
              'group',
              ),
            'published' => array(0, 1),
            ),
          'columns' => array(
            'name' => t('Название'),
            'login' => t('Внутреннее'),
            'updated' => t('Изменена'),
            'created' => t('Добавлена'),
            ),
          'sortable' => array(
            'name',
            'login',
            'updated',
            'created',
            ),
          'limit' => 10,
          ),
        ),
      'BebopSubscription' => array(
        'title' => 'Управление подпиской',
        'class' => 'SubscriptionAdminWidget',
        'internal' => true,
        'config' => array(
          'groups' => array(
            'Subscription Managers',
            ),
          ),
        ),
      'BebopLogs' => array(
        'title' => 'Системный журнал',
        'class' => 'LogAdminWidget',
        'internal' => true,
        'config' => array(
          'limit' => 10,
          'groups' => array(
            'Site Managers',
            ),
          ),
        ),
      'BebopDrawText' => array(
        'title' => 'Рендеринг надписей в PNG',
        'class' => 'DrawTextWidget',
        'internal' => true,
        ),
      );

    // Создаём отсутствующие и обновляем существующие виджеты.
    foreach ($widgets as $k => $v) {
      $nodes = Node::find(array('class' => 'widget', 'name' => $k));

      if (empty($nodes)) {
        $node = Node::create('widget');
        // printf("  creating widget %s\n", $k);
      } else {
        $node = array_shift($nodes);
      }

      $nid = $node->id;
      $node->name = $k;
      $node->title = $v['title'];
      $node->classname = $v['class'];
      $node->config = empty($v['config']) ? null : $v['config'];
      $node->internal = !empty($v['internal']);

      try {
        $node->save();

        if ($nid === null) {
          // printf("    saved.\n");
        }
      } catch (PDOException $e) {
        // printf("    could not save: %s\n", $e->getMessage());
      }
    }
  }

  // Обновление административных страниц.
  private function doUpdatePages()
  {
    // Структура страниц, которые мы будем создавать.
    $schema = array(
      'admin' => array(
        'title' => 'Molinos.CMS',
        'http_code' => 200,
        'theme' => 'admin',
        'language' => 'ru',
        'access' => array('Content Managers', 'Structure Managers', 'Schema Managers', 'Developers', 'User Managers'),
        'widgets' => array('BebopDashboard', 'profile', 'BebopStatus'),
        'pages' => array(
          'taxonomy' => array(
            'title' => 'Карта сайта',
            'description' => 'Управление разделами, структурой данных.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Structure Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopStructure'),
            'params' => 'sec',
            ),
          'schema' => array(
            'title' => 'Типы документов',
            'description' => 'Типы документов, используемые на сайте.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Schema Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopSchema'),
            ),
          'content' => array(
            'title' => 'Наполнение',
            'description' => 'Наполнение сайта.&nbsp; Поиск, редактирование, добавление документов.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Content Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopContentList'),
            'params' => 'sec',
            'pages' => array(
              'filter' => array(
                'title' => 'Настройка фильтра',
                'description' => 'Здесь можно настроить выборку документов.',
                'http_code' => 200,
                'theme' => 'admin',
                'language' => 'ru',
                'access' => array('Content Managers'),
                'widgets' => array('BebopDashboard', 'profile', 'BebopContentFilterSettings'),
                ),
              'create' => array(
                'title' => 'Добавление объекта',
                'description' => 'Здесь можно создать новый документ.',
                'http_code' => 200,
                'theme' => 'admin',
                'language' => 'ru',
                'access' => array('Content Managers'),
                'widgets' => array('BebopDashboard', 'profile', 'BebopContentCreate'),
                ),
              ),
            ),
          'builder' => array(
            'title' => 'Конструктор',
            'description' => 'Управление доменами, страницами, виджетами.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Developers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopWidgets'),
            'pages' => array(
              'widgets' => array(
                'title' => 'Виджеты',
                'description' => 'Общий список виджетов',
                'http_code' => 200,
                'theme' => 'admin',
                'language' => 'ru',
                'access' => array('Developers'),
                'widgets' => array('BebopDashboard', 'profile', 'BebopWidgetList'),
                ),
              ),
            ),
          'users' => array(
            'title' => 'Пользователи',
            'description' => 'Управление профилями пользователей и группами.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('User Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopUsers'),
            'pages' => array(
              'groups' => array(
                'title' => 'Группы',
                'http_code' => 200,
                'theme' => 'admin',
                'language' => 'ru',
                'access' => array('User Managers'),
                'widgets' => array('BebopDashboard', 'profile', 'BebopGroups'),
                ),
              ),
            ),
          'update' => array(
            'title' => 'Обновление',
            'description' => 'Позволяет администраторам обновлять системую',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Site Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopUpdates'),
            'hidden' => 1,
            ),
          'logs' => array(
            'title' => 'Журнал событий',
            'description' => 'Журнал: кто, что, когда и с каким документом делал.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Access Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopLogs'),
            ),
          'files' => array(
            'title' => 'Файлы',
            'description' => 'Список задействованных в наполнении файлов.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Content Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopFiles'),
            'pages' => array(
              'picker' => array(
                'title' => 'Выбиралка',
                'description' => 'Список задействованных в наполнении файлов.',
                'http_code' => 200,
                'theme' => 'admin',
                'language' => 'ru',
                'access' => array('Content Managers'),
                'widgets' => array('BebopFiles'),
                ),
              ),
            ),
          'subscription' => array(
            'title' => 'Рассылка',
            'description' => 'Управление подпиской на новости.',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Subscription Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopSubscription'),
            ),
          'node' => array(
            'title' => 'Редактирование объекта',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Visitors'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopNode'),
            'hidden' => true,
            ),
          'trash' => array(
            'title' => 'Корзина',
            'http_code' => 200,
            'theme' => 'admin',
            'language' => 'ru',
            'access' => array('Content Managers'),
            'widgets' => array('BebopDashboard', 'profile', 'BebopRecycleBin'),
            ),
          ),
        ),
      );

    // Находим основной домен.
    try {
      $root = Node::load(array('class' => 'domain', 'name' => 'DOMAIN'));
    } catch (ObjectNotFoundException $e) {
      try {
        $root = Node::load(array('class' => 'domain', 'name' => 'www.DOMAIN'));
      } catch (ObjectNotFoundException $e) {
        $root = Node::create('domain');
        $root->name = 'DOMAIN';
        $root->title = $_SERVER['HTTP_HOST'];
        $root->description = t("Это &mdash; главная страница сайта, на который недавно была установлена система <a href='@cmslink'>Molinos.CMS</a>. Сейчас вам, скорее всего, нужен <a href='@adminlink'>административный интерфейс</a>, а что и как настраивать вы можете узнать в <a href='@doclink'>документации</a>.", array(
          '@cmslink' => 'http://code.google.com/p/molinos-cms/',
          '@adminlink' => '/admin/',
          '@doclink' => 'http://code.google.com/p/molinos-cms/w/list',
          ));
        $root->http_code = 200;
        $root->theme = 'example';
        $root->language = 'ru';
        $root->save();

        // printf("  base domain created\n");
      }
    }

    self::doUpdateBranch($root, $schema);
  }

  // Воссоздание ветки страниц.
  private static function doUpdateBranch(Node $root, array $urls, $depth = 0)
  {
    $pdo = PDO_Singleton::getInstance();

    foreach ($urls as $name => $info) {
      // Пытаемся найти существующую ветку.
      try {
        $branch = Node::load(array('class' => 'domain', 'name' => $name, 'parent_id' => $root->id));
      }

      // Создаём новую ветку.
      catch (ObjectNotFoundException $e) {
        $branch = Node::create('domain', array('parent_id' => $root->id));
        $branch->name = $name;
        // printf("  %s+ %s\n", str_repeat(' ', $depth), $info['title']);
      }

      // Обновляем свойства.
      foreach ($info as $k => $v) {
        if (!is_array($v))
          $branch->$k = $v;
      }

      // Удаляем разметку параметров, если не нужна.
      if (!array_key_exists('params', $info) and !empty($branch->params))
        unset($branch->params);

      // Сохраняем страницу.
      try {
        $branch->save(false);
      } catch (PDOException $e) {
        // printf("      error saving this page: %s\n", var_export($branch, true));
        throw $e;
      }

      // Сохраняем права.
      $perms = array();
      if (!empty($info['access'])) {
        foreach ($info['access'] as $gid)
          $perms[$gid] = array('r');
      }
      try {
        $branch->setAccess($perms, true);
      } catch (PDOException $e) {
        throw new Exception("Could not set permissions for domain {$branch->id}.");
      }

      // Цепляем виджеты.
      try {
        $pdo->exec("DELETE FROM `node__rel` WHERE `tid` = :page AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'widget')", array(':page' => $branch->id));
        if (!empty($info['widgets'])) {
          $sql = "INSERT INTO `node__rel` (`tid`, `nid`) SELECT :page, `n`.`id` FROM `node` `n` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` WHERE `n`.`class` = 'widget' AND `r`.`name` IN ('". join("', '", $info['widgets']) ."')"; 
//          bebop_debug($sql);
          $pdo->exec($sql, array(':page' => $branch->id));
        }
      } catch (PDOException $e) {
        throw new Exception("Could not attach widgets to a page: ".$e->getMessage());
      }

      // Идём вглубь.
      if (array_key_exists('pages', $info))
        self::doUpdateBranch($branch, $info['pages'], $depth + 2);
    }
  }

  // РАБОТА С КРОНОМ.
  public static function taskRun()
  {
    /*
    $um = new UpdateManager();
    $path = $um->getReleaseInfoPath();

    $data = mcms_fetch_file($path, true);

    if (file_exists($cachepath))
      if (!unlink($cachepath))
        throw new Exception(t('Не удалось сохранить информацию об обновлениях.'));

    file_put_contents($cachepath, $data);
    */
  }
};
