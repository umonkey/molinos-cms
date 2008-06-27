<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ExchangeModule implements iRemoteCall, iAdminMenu, iAdminUI
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

  public static function hookRemoteCall(RequestContext $ctx)
  {
    $exchmode = $ctx->post('exchmode');
    $result = '';
    $themes = array();

    if ($exchmode == 'export') { // Экспорт профиля
      $expprofiledescr = trim($ctx->post('expprofiledescr'));
      $expprofilename = $_SERVER['HTTP_HOST'] ."_". date("Y-m-d");

      if (empty($expprofiledescr))
        $expprofiledescr = "Этот профиль был экспортирован с сайта ". $_SERVER['HTTP_HOST'];

      $xmlstr = self::export($expprofilename, $expprofiledescr);

      $xml = new SimpleXMLElement($xmlstr);

      $zipfile = "siteprofile.zip";
      $zipfilepath = mcms::config('tmpdir') ."/export/{$zipfile}";

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
    else if ($exchmode == 'import') { // Импорт профиля
      $fn = basename($_FILES['impprofile']['name']);

      $newfn = mcms::config('tmpdir') .'/import/'. $fn;

      mcms::mkdir(dirname($newfn), t('Не удалось создать временный каталог для импорта данных.'));

      move_uploaded_file($_FILES['impprofile']['tmp_name'], $newfn);

      $filetype = substr($fn, -4);

      if ($filetype == '.xml') { // Это не архив, а xml
        $xmlstr = implode(file($newfn), $arr);
      }
      else if ($filetype == '.zip') { //Это zip-архив
        $zip = new ZipArchive;
        $zip->open($newfn);
        $xmlstr = $zip->getFromName("siteprofile.xml");
      }
      else { // неизвестный тип файла
         mcms::redirect("admin?mode=exchange&preset=export&result=badfiletype");
      }

      mcms::db()->clearDB();

      //Installer::CreateTables();

      if ($filetype == '.zip') {
        $zip->extractTo(MCMS_ROOT);
        $zip->close();
      }

      self::import($xmlstr);
      unlink($newfn);

      mcms::redirect("admin?mode=exchange&preset=export&result=importok");
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
      $olddsn = mcms::db()->getConfig('default');

      $xmlstr = self::export('Mysql-upgrade', 'Профиль для апгрейда до MySQL');

      // функция очистки базы делает также её бэкап
      mcms::db()->clearDB();

      // запишем конфиг новым dsn
      InstallModule::writeConfig($data, $olddsn);

      // принудительный перевод PDO_Singleton в Mysql
      PDO_Singleton::getInstance('default', true);

      // Перед импортом нужно очистить целевую базу данных,
      // чтобы не получить исключение о дубликатах.
      mcms::db()->clearDB();
      self::import($xmlstr);

      // Логинимся в качестве рута.
      User::authorize(mcms::user()->name, null, true);

      mcms::redirect("admin?module=exchange&preset=export&result=upgradeok");
    }
  }

  //экспорт профиля
  public static function export($profilename, $profiledescr, $arg = array())
  {
    $list = Node::find($arg);

    $str = "<?xml version=\"1.0\" standalone=\"yes\"?>\n";
    $str .= "<root>\n<info name='{$profilename}'>\n<description><![CDATA[{$profiledescr}]]></description>\n</info>\n";
    $str .= "<nodes>\n";

    foreach ($list as $tmp) {
      $arr = $tmp->getRaw();
      $arrarr = array();
      $srlz = "\n";

      foreach ($arr as $key => $val) {
        if ('left' == $key or 'right' == $key or 'rid' == $key or empty($val) or ('code' == $key and is_numeric($val))) {
          unset($arr[$key]);
          continue;
        }

        if (is_array($val)) {
          $arrarr[$key] = $val;
          $srlz .= mcms::html($key, "<![CDATA[". serialize($val) ."]]>") ."\n";
          unset($arr[$key]);
        }
      }

      $str .= mcms::html('node', $arr, $srlz) ."\n";
    }

    $str .= "</nodes>\n";
    $str .= "<links>\n";

    $arr = mcms::db()->getResults("SELECT `tid`, `nid`, `key`, `order` FROM `node__rel` ORDER BY `tid`, `order`");

    foreach ($arr as $el)
      $str .= mcms::html('link', $el) ."\n";

    $str .= "</links>\n";
    $str .= "<accessrights>\n";

    $arr = mcms::db()->getResults("SELECT `nid`, `uid`, `c`, `r`, `u`, `d`, `p` FROM `node__access` ORDER BY `nid`");

    foreach ($arr as $el)
      $str .= mcms::html('access', $el) ."\n";

    $str .= "</accessrights>\n";
    $str .= "</root>\n";

    return $str;
  }

  // импорт профиля
  public static function import($source, $isfile = false)
  {
    if ($isfile) {
      $arr = file($source);

      if (!$arr)
        return 0;

      $xmlstr = implode('', $arr);
    } else {
      $xmlstr = $source;
    }

    mcms::db()->beginTransaction();

    $xml = new SimpleXMLElement($xmlstr);

    $Nodes = array();
    $curnode = array();
    $larr = array();
    $newid = array();

    foreach ($xml->nodes->node as $node) {
      $curnode = array();

      foreach ($node->attributes() as $a => $v)
        $curnode[$a] = strval($v);

      foreach($node as $attr => $val) {
        $obj = unserialize($val);
        $curnode[$attr] = $obj;
      }

      $oldid = $curnode['id'];

      foreach (array('id', 'rid', 'left', 'right') as $k)
        if (array_key_exists($k, $curnode))
          unset($curnode[$k]);

      $SiteNode = Node::create(strval($node['class']), $curnode);
      $SiteNode->save();

      $curid = $SiteNode->id;
      $newid[$oldid] = $curid; //ставим соответствие между старым id и новым
    }

    $at = array();
    //внесём записи в `node__rel`
    foreach ($xml->links->link as $link) {
      foreach ($link->attributes() as $a => $v)
        $at[$a] = strval($v);

      $n = $at['nid'];
      $t = $at['tid'];

      if (array_key_exists($n,$newid))
        $nid = $newid[$n];
      if (array_key_exists($t,$newid))
        $tid = $newid[$t];

      if (!empty($nid) and !empty($tid)) {
        $key = null;
        $order = null;

        if (array_key_exists('order', $v))
          $order = $attr['order'];

        if (array_key_exists('key', $v))
          $key = $attr['key'];

        mcms::db()->exec("INSERT INTO `node__rel` (`tid`, `nid`, `key`, `order`) VALUES (:tid, :nid, :key, :order)",          array(
          ':tid' => $tid,
          ':nid' => $nid,
          ':key' => $key,
          ':order' => $order
          ));
      }
    }

    // Внесём записи в `node__access`

    foreach ($xml->accessrights->access as $acc) {
      $at = array();

      foreach ($acc->attributes() as $a => $v)
        $at[$a] = strval($v);

      $nd = $at['nid'];
      $ud = empty($at['uid']) ? 0 : $at['uid'];

      if (array_key_exists($nd, $newid))
        $nid = $newid[$nd];

      if (array_key_exists($ud, $newid))
        $uid = $newid[$ud];

      if (!empty($nid)) {
        $c = empty($at['c']) ? 0 : 1;
        $r = empty($at['r']) ? 0 : 1;
        $u = empty($at['u']) ? 0 : 1;
        $d = empty($at['d']) ? 0 : 1;
        $p = empty($at['p']) ? 0 : 1;

        mcms::db()->exec("INSERT INTO `node__access`(`nid`, `uid`, `c`, `r`, `u`, `d`, `p`) VALUES (:nid, :uid, :c, :r, :u, :d, :p)", array(
          ':nid' => $nid,
          ':uid' => $uid,
          ':c' => $c,
          ':r' => $r,
          ':u' => $u,
          ':d' => $d,
          ':p' => $p,
          ));
      }
    }

    //обновим parent_id в таблице node в соответствиями со значениями из массива $newid
    foreach ($newid as $oldid=>$curid) {
      mcms::db()->exec("UPDATE `node` SET parent_id=:newid where parent_id=:oldid ", array(
        ':newid' => $curid,
        ':oldid' => $oldid
        ));
    }

    return 1;
  }

  public static function getProfileList()
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

    return $plist;
  }

  public static function getMenuIcons()
  {
    $icons = array();

      $icons[] = array(
        'group' => 'structure',
        'href' => 'admin?module=exchange',
        'title' => t('Бэкапы'),
        'description' => t('Бэкап и восстановление данных в формате XML.'),
        );

    return $icons;
  }

  public static function onGet(RequestContext $ctx)
  {
    if (null !== ($tmp = $ctx->get('result')))
      return self::getResult($tmp);

    $form = new Form(array(
      'title' => t('Экспорт/импорт сайта в формате XML'),
      'description' => t("Необходимо выбрать совершаемое вами действие"),
      'action' => 'exchange.rpc',
      'class' => '',
      'id' => 'mod_exchange'
      ));

    $resstr = array (
      'noprofilename' => 'Ошибка: не введено имя профиля',
      'noimpprofile' => 'Ошибка: не выбран профиль для импорта',
      'notopenr' => 'Ошибка: невозможно открыть файл на чтение',
      'badfiletype' => 'Неподдерживаемый тип файла. Файл должен быть формата XML или ZIP',
      'upgradeok' => 'Upgrade до Mysql прошёл успешно'
      );

    /*
    if ($result)
      $form->addControl(new InfoControl(array('text' => $resstr[$result])));
    */

    $options = array(
       'export' => t('Бэкап'),
       'import' => t('Восстановление'),
       );

    if (mcms::db()->getDbType() == 'SQLite')
      $options['upgradetoMySQL'] = t('Перенести данные в MySQL');

    $form->addControl(new EnumRadioControl(array(
       'value' => 'exchmode',
       'label' => t('Выберите действие'),
       'default' => 'import',
       'options' => $options
        )));

    $form->addControl(new TextAreaControl(array(
      'value' => 'expprofiledescr',
      'label' => t('Комментарий к бэкапу'),
      )));

    $plist = ExchangeModule::getProfileList();
    $options = array();

    for ($i = 0; $i < count($plist); $i++) {
      $pr = $plist[$i];
      $options[$pr['filename']] = $pr['name'];
    }

    $form->addControl(new AttachmentControl(array(
      'label' => t('Выберите импортируемый профиль'),
      'value' => 'impprofile'
      )));

    if (mcms::db()->getDbType() == 'SQLite') {
      $form->addControl(new TextLineControl(array(
        'value' => 'db[name]',
        'label' => t('Имя базы данных'),
        'description' => t("Перед инсталляцией база данных будет очищена от существующих данных, сделайте резервную копию!"),
        )));

      $form->addControl(new TextLineControl(array(
        'value' => 'db[host]',
        'label' => t('MySQL сервер'),
        'wrapper_id' => 'db-server',
        'default' => 'localhost',
        )));

      $form->addControl(new TextLineControl(array(
        'value' => 'db[user]',
        'label' => t('Пользователь MySQL'),
        'wrapper_id' => 'db-user',
        )));

      $form->addControl(new PasswordControl(array(
        'value' => 'db[pass]',
        'label' => t('Пароль этого пользователя'),
        'wrapper_id' => 'db-password',
        )));
    }

    $form->addControl(new SubmitControl(array(
      'text' => t('Произвести выбранную операцию'),
       )));

    return $form->getHTML(array());
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
