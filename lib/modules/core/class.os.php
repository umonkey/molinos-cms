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
   * Возвращает путь, пригодный для использования в вебе.
   */
  public static function webpath()
  {
    $components = func_get_args();
    return str_replace(DIRECTORY_SEPARATOR, '/', implode('/', $components));
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
   * Возвращает список всех файлов в папке, рекурсивно.
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

  public static function find()
  {
    $path = func_get_args();
    $path = join(DIRECTORY_SEPARATOR, $path);
    return glob($path);
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

    if (strlen($content) != @file_put_contents($fileName, $content))
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
    if (false === ($pos = strrpos($fileName, '.')))
      return null;
    return strtolower(substr($fileName, $pos + 1));
  }

  /**
   * Определяет тип файла по содержимому, разными способами.
   */
  public static function getFileType($fileName, $realName = null)
  {
    $result = 'application/octet-stream';

    if (function_exists('mime_content_type'))
      $result = mime_content_type($fileName);

    elseif (function_exists('finfo_open')) {
      if (false !== ($r = @finfo_open(FILEINFO_MIME))) {
        $result = finfo_file($r, $fileName);
        $result = str_replace(strrchr($result, ';'), '', $result);
        finfo_close($r);
      }
    }

    // Не удалось, гадаем по расширению.
    if ('application/octet-stream' == $result and null !== $realName) {
      switch (strtolower(substr($realName, strrpos($realName, '.')))) {
      case '.pdf':
        return 'application/pdf';
      case '.desktop':
        return 'application/x-gnome-shortcut';
      case '.bmp':
        return 'image/bmp';
      case '.gif':
        return 'image/gif';
      case '.jpg':
      case '.jpeg':
        return 'image/jpeg';
      case '.png':
        return 'image/png';
      case '.mp3':
        return 'audio/mpeg';
      case '.php':
      case '.txt':
        return 'text/plain';
      case '.zip':
        return 'application/zip';
      case '.flv':
        return 'video/flv';
      case '.ttf':
        return 'application/x-font-ttf';
      }
    }

    return $result;
  }

  /**
   * Копирование файла. Поддерживает загруженные файлы.
   */
  public static function copy($src, $dst)
  {
    if (is_uploaded_file($src))
      return move_uploaded_file($src, $dst);
    if (!file_exists($src))
      throw new InvalidArgumentException(t('Попытка скопировать несуществующий файл (%path).', array(
        '%path' => $src,
        )));
    if (!is_writable(dirname($dst)))
      throw new RuntimeException(t('Каталог %path закрыт от записи.', array(
        '%path' => dirname($dst),
        )));
    return copy($src, $dst);
  }

  /**
   * Очистка имени файла от мусора.
   */
  public static function getCleanFileName($fileName)
  {
    if ($sfx = strrchr($fileName, '.'))
      $fileName = substr($fileName, 0, - strlen($sfx));
    $fileName = trim(preg_replace('/[^a-z0-9_-]+/', '_', $fileName), '_') . $sfx;
    return $fileName;
  }

  /**
   * Создаёт папку со всеми необходимыми родителями.
   */
  public static function mkdir($path, $errormsg = null)
  {
    if (!is_dir($path) and !mkdir($path, 0775, true)) {
      if (null === $errormsg)
        return false;
      throw new RuntimeException($errormsg);
    }

    return realpath($path);
  }
}
