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

      if (!is_dir(dirname($zipfilepath)))
        mkdir(dirname($zipfilepath));

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
             $thm = $curnode['theme'];
             self::addToZip($_SERVER["DOCUMENT_ROOT"] ."/themes/{$thm}", $zip, "themes/{$thm}");
          }
        }
      }

      $zip->addFromString("siteprofile.xml", $xmlstr);
      $zip->close();

      if (!file_exists($zipfilepath))
        throw new RuntimeException(t('Не удалось экспортировать данные.'));

      header ("Content-Type: application/octet-stream");
      header ("Accept-Ranges: bytes");
      header ("Content-Length: ". filesize($zipfilepath));
      header ("Content-Disposition: attachment; filename=". $zipfile);

      readfile($zipfilepath);
      unlink($zipfilepath);
      exit();
    }
    else if ($exchmode == 'import') { // Импорт профиля
      $fn = basename($_FILES['impprofile']['name']);
      $newfn = $_SERVER["DOCUMENT_ROOT"] ."/tmp/import/{$fn}";
      if (!is_dir(dirname($newfn)))
        mkdir(dirname($newfn));

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
         bebop_redirect("/admin/?mode=exchange&preset=export&result=badfiletype");
      }

      mcms::db()->clearDB();

      Installer::CreateTables();

      if ($filetype == '.zip') {
        $zip->extractTo($_SERVER["DOCUMENT_ROOT"]);
        $zip->close();
      }

      self::import($xmlstr);
      unlink($newfn);

      bebop_redirect("/admin/?mode=exchange&preset=export&result=importok");
    }

    else if ($exchmode == 'upgradetoMySQL') {
      $data = $ctx->post;
      $data['confirm'] = 1;
      $data['config']['debuggers'] = $_SERVER['REMOTE_ADDR'] .', 127.0.0.1';
      $data['db']['type'] = 'mysql';

      $olddsn = mcms::db()->getConfig('default');

      $xmlstr = self::export('Mysql-upgrade', 'Профиль для апгрейда до MySQL');
      mcms::db()->clearDB(); // функция очистки базы делает также её бэкап

      Installer::WriteConfig($data,$olddsn); //запишем конфиг новым dsn

      PDO_Singleton::getInstance('default', true); // принудительный перевод PDO_Singleton в Mysql

      Installer::CreateTables();

      self::import($xmlstr);

      // Логинимся в качестве рута.
      User::authorize('root', null, true);

      bebop_redirect("/admin/?module=exchange&preset=export&result=upgradeok");
    }
  }

  //экспорт профиля
  public static function export($profilename, $profiledescr, $arg = array())
  {
    $list = Node::find($arg);

    $str = "<?xml version=\"1.0\" standalone=\"yes\"?>";
    $str .= "<root><info name='{$profilename}'><description><![CDATA[{$profiledescr}]]></description></info>\n";
    $str .= "<nodes>";

    foreach ($list as $tmp) {
      $arr = $tmp->getRaw();
      $arrarr = array();
      $srlz = "";

      foreach ($arr as $key => $val) {
        if ($key == 'code' and is_numeric($val))
          continue;

        if (is_array($val)) {
          $arrarr[$key] = $val;
          $srlz .= mcms::html($key, array(), "<![CDATA[". serialize($val) ."]]>");
          unset($arr[$key]);
        }
      }

      $str .= mcms::html('node', $arr, $srlz);
    }

    $str .= "</nodes>";
    $str .= "<links>";

    $arr = mcms::db()->getResults("SELECT `tid`, `nid`, `key`, `order` FROM `node__rel` ORDER BY `tid`, `order`");

    foreach ($arr as $el)
      $str .= mcms::html('link',$el);

    $str .= "</links>";
    $str .= "<accessrights>";

    $arr = mcms::db()->getResults("SELECT `nid`, `uid`, `c`, `r`, `u`, `d` FROM `node__access` ORDER BY `nid`");

    foreach ($arr as $el)
      $str .= mcms::html('access', $el);

    $str .= "</accessrights>";
    $str .= "</root>";

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

    $at = array();

    //внесём записи в `node__access`
    foreach ($xml->accessrights->access as $acc) {
      foreach ($acc->attributes() as $a => $v)
        $at[$a] = strval($v);

      $nd = $at['nid'];
      $ud = $at['uid'];

      if (array_key_exists($nd,$newid))
        $nid = $newid[$nd];

      if (array_key_exists($ud,$newid))
        $uid = $newid[$ud];

      if (!empty($nid) and !empty($uid)) {

        $c = $r = $u = $d = 0;

        if (array_key_exists('c',$at))
          $c = $at['c'];
        if (array_key_exists('r',$at))
          $r = $at['r'];
        if (array_key_exists('u',$at))
          $u = $at['u'];
        if (array_key_exists('d',$at))
          $d = $at['d'];

        mcms::db()->exec("INSERT INTO `node__access`(`nid`, `uid`, `c`, `r`, `u`, `d`) VALUES (:nid, :uid, :c, :r, :u, :d)", array(
          ':nid' => $nid,
          ':uid' => $uid,
          ':c' => $c,
          ':r' => $r,
          ':u' => $u,
          ':d' => $d
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
        'href' => '/admin/?module=exchange',
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
      'action' => '/exchange.rpc',
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

    if ($result)
      $form->addControl(new InfoControl(array('text' => $resstr[$result])));

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

      $form->addControl(new SubmitControl(array(
        'text' => t('Произвести выбранную операцию'),
        )));
    }

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
      'upgradeok' => t('База данных успешно перенесена в MySQL.  Вы можете <a href=\'@continue\'>продолжить пользоваться системой</a> в обычном режиме.  Чтобы переключиться обратно на SQLite, отредактируйте конфигурационный файл Molinos.CMS (обычно это conf/default.ini).', array('@continue' => '/admin/')),
      'importok' => t('Восстановление бэкапа прошло успешно.'),
      'exportok' => null,
      );

    return '<h2>'. $titles[$mode] .'</h2><p>'. $messages[$mode] .'</p>';
  }
}
