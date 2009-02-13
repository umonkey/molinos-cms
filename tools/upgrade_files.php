<?php

function fixFileName($string)
{
  $xlat = array(
    'а' => 'a',
    'б' => 'b',
    'в' => 'v',
    'г' => 'g',
    'д' => 'd',
    'е' => 'e',
    'ё' => 'e',
    'ж' => 'zh',
    'з' => 'z',
    'и' => 'i',
    'й' => 'j',
    'к' => 'k',
    'л' => 'l',
    'м' => 'm',
    'н' => 'n',
    'о' => 'o',
    'п' => 'p',
    'р' => 'r',
    'с' => 's',
    'т' => 't',
    'у' => 'u',
    'ф' => 'f',
    'х' => 'h',
    'ц' => 'c',
    'ч' => 'ch',
    'ш' => 'sh',
    'щ' => 'sch',
    'ы' => 'y',
    'э' => 'e',
    'ю' => 'yu',
    'я' => 'ya',
    );

  $output = str_replace(array_keys($xlat), array_values($xlat), mb_strtolower($string));

  if (false !== ($sfx = strrchr($output, '.'))) {
    $output = preg_replace('/_{2,}/', '_', trim(preg_replace('/[^a-z0-9_]+/', '_', substr($output, 0, - strlen($sfx))), '_'));
    if (empty($output))
      die(sprintf("warning: %s => %s\n", $string, $output));
    $output .= $sfx;
  }

  return $output;
}

function fixFilePath($filepath)
{
  if (!preg_match('@^././[a-z0-9]{32}$@', $filepath))
    return $filepath;
  elseif (false === strpos($filepath, '.'))
    return $filepath;

  $path = dirname($filepath);
  $name = basename($filepath);

  $newname = fixFileName($filepath);

  die(var_dump($filepath, $newname));

}

function connect($dsn)
{
  $u = parse_url($dsn);

  switch ($u['scheme']) {
  case 'sqlite':
    $db = new PDO($dsn, '');
    break;
  default:
    die("Unsupported schema: {$u['scheme']}.\n");
  }

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $db;
}

function upgrade_data($db, $folder)
{
  $sth = $db->prepare("SELECT `id`, `data` FROM `node` WHERE `class` = 'file'");
  $sth->execute();

  $upd = $db->prepare("UPDATE `node` SET `data` = ?, `published` = ? WHERE `id` = ?");
  $idx = $db->prepare("UPDATE `node__idx_file` SET `filename` = ?, `filepath` = ? WHERE `id` = ?");

  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['data'])) {
      $data = unserialize($row['data']);
      if (!empty($data['filepath']) and !empty($data['filename'])) {
        $name = fixFileName(basename($data['filename']));
        $path = dirname($data['filepath']);
        $from = $data['filepath'];

        $src = $folder . DIRECTORY_SEPARATOR . $from;
        $dst = $folder . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $name;

        if (file_exists($src) and !file_exists($dst))
          rename($src, $dst);

        $data['filepath'] = $path . DIRECTORY_SEPARATOR . $name;
        $data['filename'] = $name;

        if (array_key_exists('url', $data))
          unset($data['url']);

        $upd->execute(array(
          serialize($data),
          file_exists($dst),
          $row['id'],
          ));

        $idx->execute(array(
          $name,
          $path . DIRECTORY_SEPARATOR . $name,
          $row['id'],
          ));
      }
    }
  }
}

function upgrade($dsn, $path)
{
  mb_internal_encoding('utf-8');

  $db = connect($dsn);
  $db->beginTransaction();

  // upgrade_files($db, $path);
  upgrade_data($db, $path);

  $db->commit();
  return 0;
}

if (empty($argv[2])) {
  printf("Usage: %s dsn filefolder\n", basename($argv[0]));
  exit(1);
}

return upgrade($argv[1], $argv[2]);
