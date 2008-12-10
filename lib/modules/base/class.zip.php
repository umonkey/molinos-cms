<?php
/**
 * Функции для работы с ZIP архивами.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class zip
{
  public static function fromFolder($zipName, $folderName)
  {
    $folderName = rtrim($folderName, DIRECTORY_SEPARATOR);

    $z = new ZipArchive();
    $z->open($zipName, ZIPARCHIVE::OVERWRITE);

    foreach (os::listFiles($folderName) as $file)
      $z->addFile($file, substr($file, strlen($folderName) + 1));

    $z->close();
  }
}
