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
  public static function isAvailable()
  {
    return class_exists('ZipArchive');
  }

  public static function fromFolder($zipName, $folderPath, $exclude = null)
  {
    if (!self::isAvailable())
      throw new ZipException();

    $folderPath = rtrim($folderPath, DIRECTORY_SEPARATOR);

    if (!is_writable(dirname($zipName)))
      throw new RuntimeException(t('Невозможно создать %name: папка защищена от записи.', array(
        '%name' => $zipName,
        )));

    if (file_exists($zipName))
      unlink($zipName);

    $z = new ZipArchive();
    if (true !== ($res = $z->open($zipName, ZIPARCHIVE::CREATE)))
      throw new RuntimeException(t('Не удалось создать архив %name.', array(
        '%name' => $zipName,
        )));

    foreach (os::listFiles($folderPath, $exclude) as $file)
      $z->addFile($file, substr($file, strlen($folderPath) + 1));

    $z->close();
  }

  public static function unzipToFolder($zipName, $folderPath)
  {
    if (!self::isAvailable())
      throw new ZipException();

    $tmpDir = file_exists($folderPath)
      ? $folderPath . '.tmp'
      : $folderPath;

    if ($tmpDir != $folderPath and file_exists($tmpDir))
      os::rmdir($tmpDir);

    $z = new ZipArchive();

    if (true !== ($error = $z->open($zipName)))
      throw new RuntimeException(t('Не удалось открыть ZIP архив %name', array(
        '%name' => basename($zipName),
        )));

    if (!$z->extractTo($tmpDir)) {
      if ($tmpDir != $folderPath)
        os::rmdir($tmpDir);
      throw new RuntimeException(t('Не удалось распаковать содержимое архива в папку %path.', array(
        '%path' => $tmpDir,
        )));
    }

    if ($tmpDir != $folderPath) {
      if (!rename($folderPath, $old = $folderPath . '.old')) {
        os::rmdir($tmpDir);
        throw new RuntimeException(t('Не удалось переименовать временную папку.'));
      }

      rename($tmpDir, $folderPath);

      os::rmdir($old);
    }
  }
}

class ZipException extends Exception
{
  public function __construct()
  {
    parent::__construct(t('Функции для работы с ZIP архивами недоступны.'));
  }
}
