<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ExchangeModule implements iRemoteCall
{
  // Добавляет в zip-архив указанный каталог
  function addToZip($fld, $zip, $from)
  {
    $hdl = opendir($fld);

    while ($file = readdir($hdl)) {
      if (($file != ".") and ($file != "..")) {
        $curfile = $fld."/".$file;

        if (is_dir($curfile))
          self::addToZip($curfile, $zip, $from ."/". $file);
        else
          $zip->addFile($curfile, $from ."/". $file);
       }
    }

    closedir($hdl);
  }

  public static function hookRemoteCall(Context $ctx)
  {
    return mcms::dispatch_rpc(__CLASS__, $ctx);

    if (!class_exists('ZipArchive'))
      throw new RuntimeException(t('Функции резервного копирования используют расширение zip, которое отсутствует.'));

    $exchmode = $ctx->post('exchmode');
    $result = '';
    $themes = array();

    if ($exchmode == 'export') { // Экспорт профиля
    }
    else if ($exchmode == 'import') { // Импорт профиля
      $fn = basename($_FILES['impprofile']['name']);

      $newfn = mcms::config('tmpdir') .'/import/'. $fn;

      mcms::mkdir(dirname($newfn), t('Не удалось создать временный каталог для импорта данных.'));

      move_uploaded_file($_FILES['impprofile']['tmp_name'], $newfn);

      $filetype = substr($fn, -4);

      if ($filetype == '.xml') { // Это не архив, а xml
        $xmlstr = file_get_contents($newfn);
      }
      else if ($filetype == '.zip') { //Это zip-архив
        $zip = new ZipArchive;
        $zip->open($newfn);
        $xmlstr = $zip->getFromName("siteprofile.xml");
      }
      else { // неизвестный тип файла
        return new Redirect('?q=admin&mode=exchange&preset=export&result=badfiletype');
      }

      if ($filetype == '.zip') {
        $zip->extractTo(MCMS_ROOT);
        $zip->close();
      }

      self::import($xmlstr);
      unlink($newfn);

      return new Redirect('?q=admin&mode=exchange&preset=export&result=importok');
    }

    else if ($exchmode == 'upgradetoMySQL') {
      $data = $ctx->post;
      $data['confirm'] = 1;
      $data['config']['debuggers'] = $_SERVER['REMOTE_ADDR'] .', 127.0.0.1';
      $data['db']['type'] = 'mysql';

      // Сперва нужно проверить, запущена ли база данных, а то можно
      // грохнуть инсталяцию и при этом не выгрузить никаких данных.
      // Восстановить потом можно, но все-таки лучше семь раз отмерить,
      // а потом один раз отлить.  Если имя базы не задано, то PDO
      // ругаться не будет, так что надо проверить вручную.
      if (empty($data['db']['name']))
      	throw new RuntimeException('Не задано имя базы данных MySQL.');

      // Поскольку в PDO_Singleton нет возможности задать DSN из вне,
      // то проверку нужно осуществить вручную.  Перехватывать исключение
      // нет смысла, так как развернутое описание будет и так присутствовать.
      $newdsn = "mysql:host={$data['db']['host']};dbname={$data['db']['name']}";
      new PDO($newdsn, $data['db']['user'], $data['db']['pass'][0]);

      // Конфигурацию нужно сначала записать в файл, иначе при получении
      // инстанса PDO будет возвращаться старый коннектор.
      $olddsn = $ctx->db->getConfig('default');

      $xmlstr = self::export('Mysql-upgrade', 'Профиль для апгрейда до MySQL');

      // запишем конфиг новым dsn
      InstallModule::writeConfig($data, $olddsn);

      // принудительный перевод PDO_Singleton в Mysql
      PDO_Singleton::getInstance('default', true);

      // Перед импортом нужно очистить целевую базу данных,
      // чтобы не получить исключение о дубликатах.
      // Функция очистки базы делает также её бэкап.
      $ctx->db->clearDB();
      self::import($xmlstr);

      // Логинимся в качестве рута.
      User::authorize(mcms::user()->name, null, true);

      return new Redirect('?q=admin&module=exchange&preset=export&result=upgradeok');
    }
  }

  public static function rpc_redirect(Context $ctx)
  {
    switch ($mode = $ctx->post('mode')) {
    case 'backup':
      return self::rpc_export($ctx);
    case 'restore':
      $form = ExchangeUI::getFormFields('restore');
      $data = $form->getFormData($ctx, Node::create('exchange'));

      switch ($data->profile) {
      case 'manual':
        if (empty($data->file))
          throw new ValidationException($form['file']->label, t('Вы не выбрали файл, который нужно импортировать.'));
        $path = os::path(mcms::config('filestorage'), $data->file->filepath);
        break;
      default:
        $path = os::path('lib', 'modules', 'exchange', 'profiles', $data->profile);
        break;
      }

      if (!file_exists($path))
        throw new RuntimeException(t('Файл «%file» не найден.', array(
          '%file' => $path,
          )));

      if (!self::import($path, true))
        throw new RuntimeException(t('Не удалось загрузить профиль.'));

      $ctx->redirect('?q=admin');
    }

    $ctx->redirect('?q=admin&module=exchange&mode=' . $mode);
  }

  public static function rpc_export(Context $ctx)
  {
    $expprofiledescr = t('Резервная копия.');
    $expprofilename = $_SERVER['HTTP_HOST'] ."_". date("Y-m-d");

    $xmlstr = self::export($expprofilename, $expprofiledescr);

    $xml = new SimpleXMLElement($xmlstr);

    $zipfile = "siteprofile.zip";
    $zipfilepath = os::path(mcms::config('tmpdir'), 'export', $zipfile);

    mcms::mkdir(dirname($zipfilepath), t('Не удалось создать временный каталог для экспорта данных.'));

    $zip = new ZipArchive;
    $zip->open($zipfilepath, ZipArchive::OVERWRITE);

    $Nodes = array();

    foreach ($xml->nodes->node as $node) {
      $curnode = array();

      foreach ($node->attributes() as $a => $v)
        $curnode[$a] = strval($v);

      if ($curnode['class'] == 'file') {
        $fpath = $curnode['filepath'];
        $filestorage = mcms::config('filestorage');
        $zip->addFile($filestorage ."/".$fpath, "{$filestorage}/{$fpath}");
      }

      if ($curnode['class'] == 'domain') {
        if (array_key_exists('theme', $curnode)) {
          if (is_dir($thm = $curnode['theme']))
            self::addToZip(MCMS_ROOT ."/themes/{$thm}", $zip, "themes/{$thm}");
        }
      }
    }

    $zip->addFromString("siteprofile.xml", $xmlstr);
    $zip->close();

    if (!file_exists($zipfilepath))
      throw new RuntimeException(t('Не удалось экспортировать данные.'));

    header('Content-Type: application/octet-stream');
    header('Accept-Ranges: bytes');
    header('Content-Length: '. filesize($zipfilepath));
    header('Content-Disposition: attachment; filename='. $zipfile);

    readfile($zipfilepath);
    unlink($zipfilepath);
    exit();
  }

  //экспорт профиля
  public static function export($profilename, $profiledescr, $arg = array())
  {
    // Сортировка по левой границе обеспечивает нам
    // восстановление родителей перед восстановлением детей.
    $arg['#sort'] = array('left' => 'asc');

    $list = Node::find($arg);

    $str = "<?xml version=\"1.0\" standalone=\"yes\"?>\n";
    $str .= "<root>\n"
      ."<info name='{$profilename}'>\n"
      ."<description><![CDATA[{$profiledescr}]]></description>\n"
      ."</info>\n";
    $str .= "<nodes>\n";

    mcms::debug($list);

    foreach ($list as $tmp) {
      $arr = $tmp->getRaw();
      $arrarr = array();
      $srlz = "\n";

      $stop = array('left', 'right', 'rid');

      foreach ($arr as $key => $val) {
        if (empty($val) or in_array($key, $stop)) {
          unset($arr[$key]);
          continue;
        }

        if (is_array($val)) {
          $arrarr[$key] = $val;
          $srlz .= html::em($key, "<![CDATA[". urlencode(serialize($val)) ."]]>") ."\n";
          unset($arr[$key]);
        }
      }

      $str .= html::em('node', $arr, $srlz) ."\n";
    }

    $str .= "</nodes>\n";
    $str .= "<links>\n";

    $arr = Context::last()->db->getResults("SELECT `tid`, `nid`, `key`, `order` FROM `node__rel` ORDER BY `tid`, `order`");

    foreach ($arr as $el)
      $str .= html::em('link', $el) ."\n";

    $str .= "</links>\n";
    $str .= "<accessrights>\n";

    $arr = Context::last()->db->getResults("SELECT `nid`, `uid`, `c`, `r`, `u`, `d`, `p` FROM `node__access` ORDER BY `nid`");

    foreach ($arr as $el)
      $str .= html::em('access', $el) ."\n";

    $str .= "</accessrights>\n";
    $str .= "</root>\n";

    return $str;
  }

  // импорт профиля
  public static function import($source, $isfile = false)
  {
    if ($isfile) {
      $xmlstr = file_get_contents($source);
    } else {
      $xmlstr = $source;
    }

    mcms::db()->clearDB();
    mcms::db()->beginTransaction();

    $sax = new SaxImport();
    $sax->parse($source);

    return true;
  }

  public static function getProfileList($simple = false)
  {
    $str = dirname(__FILE__).'/profiles/'.'*.xml';

    $files = glob($str);

    $plist = array();
    $pr = array();

    foreach ($files as $fn) {
      $pr['filename']  = basename($fn);

      $arr = file($fn);
      if (!$arr)
        continue;

      $xmlstr = implode('', $arr);

      $xml = new SimpleXMLElement($xmlstr);
      $at = array();
      $info = $xml->info[0];

      foreach ($info->attributes() as $a => $v)
        $at[$a] = strval($v);

      $pr['name'] = $at['name'];
      $pr['description'] = strval($info->description);
      array_push($plist, $pr);
    }

    if ($simple) {
      $result = array();

      foreach ($plist as $k => $v)
        $result[$v['filename']] = $v['name'];

      return $result;
    }

    return $plist;
  }

  private static function getResult($mode)
  {
    $titles = array(
      'upgradeok' => t('Перенос прошёл успешно'),
      'importok' => t('Импорт прошёл успешно'),
      'exportok' => t('Экспорт прошёл успешно'),
      );

    $messages = array(
      'upgradeok' => t('База данных успешно перенесена в MySQL.  Вы можете <a href=\'@continue\'>продолжить пользоваться системой</a> в обычном режиме.  Чтобы переключиться обратно на SQLite, отредактируйте конфигурационный файл Molinos.CMS (обычно это conf/default.ini).', array('@continue' => 'admin')),
      'importok' => t('Восстановление бэкапа прошло успешно.'),
      'exportok' => null,
      );

    return '<h2>'. $titles[$mode] .'</h2><p>'. $messages[$mode] .'</p>';
  }
}
