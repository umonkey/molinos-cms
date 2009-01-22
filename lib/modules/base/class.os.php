<?php
/**
 * Функции для взаимодействия с операционной системой.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class os
{
  /**
   * Формирует путь из указанных компонентов.
   *
   * Пример: os::path(array('lib', 'modules', 'base')).
   */
  public static function path()
  {
    $components = func_get_args();
    return implode(DIRECTORY_SEPARATOR, $components);
  }

  /**
   * Формирует из абсолютного пути относительный.
   *
   * PS: относительно корня инсталляции CMS, т.е.
   * /var/www/lib/modules станет lib/modules.
   */
  public static function localpath($path)
  {
    if (0 === strpos($path, MCMS_ROOT))
      return substr($path, strlen(MCMS_ROOT) + 1);
    else
      return $path;
  }

  /**
   * Возвращает список всех файлов в папке.
   */
  public static function listFiles($path, $exclude = null)
  {
    if (!is_dir($path))
      throw new RuntimeException('os::listFiles() expects a folder path.');

    $result = array();

    $i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);

    foreach ($i as $name => $object)
      if ($object->isFile())
        if (null === $exclude or 0 !== strpos($name, $exclude))
          $result[] = $name;

    asort($result);

    return $result;
  }

  /**
   * Удаляет папку со всем её содержимым.
   */
  public static function rmdir($path)
  {
    if (!is_dir($path))
      throw new RuntimeException("os::rmdir() expects a full directory name.");

    $i = new DirectoryIterator($path);

    foreach ($i as $name => $object)
      if (!$object->isDir())
        unlink($object->getPathName());
      elseif (!$object->isDot())
        self::rmdir($object->getPathName());

    if (!rmdir($path))
      throw new RuntimeException(sprintf("os::path(%s) failed.", $path));
  }

  /**
   * Безопасная замена содержимого файла.
   *
   * Если не удастся сохранить новый файл — старый изменён не будет.
   */
  public static function write($fileName, $content)
  {
    $vpath = dirname($fileName) . DIRECTORY_SEPARATOR . basename($fileName);

    if (file_exists($fileName)) {
      if (!is_writable($fileName)) {
        if (is_writable(dirname($fileName)))
          unlink($fileName);
        else
          throw new RuntimeException(t('Изменение файла %file невозможно: он защищён от записи.', array(
            '%file' => self::localpath($vpath),
            )));
      }
    }

    if (!@file_put_contents($fileName, $content))
      throw new RuntimeException(t('Не удалось записать файл %file, проверьте права на папку %folder.', array(
        '%file' => self::localpath($vpath),
        '%folder' => dirname(self::localpath($vpath)),
        )));
  }

  /**
   * Сохраняет массив в файл.
   */
  public static function writeArray($fileName, array $content, $pretty = false)
  {
    $content = '<?php return ' . var_export($content, true) . ';';

    if ($pretty) {
      $content = preg_replace('@=>\s+array \(@', '=> array(', $content);
      $content = preg_replace('@\d+ => @', '', $content);
    }

    return self::write($fileName, $content);
  }

  /**
   * Запуск программы.
   */
  public static function exec($command, array $args, &$output = null)
  {
    $rc = null;

    foreach ($args as $arg)
      $command .= ' ' . escapeshellarg($arg);

    exec($command, $output, $rc);
    return $rc;
  }

  /**
   * Возвращает расширение файла.
   */
  public static function getFileExtension($fileName)
  {
    return strtolower(substr($fileName, strrpos($fileName, '.') + 1));
  }
}
