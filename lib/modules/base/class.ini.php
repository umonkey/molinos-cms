<?php
/**
 * Функции для работы с .ini файлами.
 *
 * @package mod_base
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class ini
{
  /**
   * Чтение файла с поддержкой секций.
   */
  public static function read($filename)
  {
    if (!file_exists($filename))
      throw new RuntimeException(sprintf("ini::read(%s): not found.", $filename));
    return parse_ini_file($filename, true);
  }

  /**
   * Запись файла с поддержкой секций.
   */
  public static function write($filename, array $data, $header = null)
  {
    $output = (null === $header)
      ? ""
      : trim($header) . "\n\n";

    // Сначала пишет простые значения
    $output .= self::write_keys($data);

    // Теперь сохраняем секции.
    foreach ($data as $k => $v)
      if (is_array($v) and !empty($v)) {
        if (!empty($output))
          $output .= "\n";
        $output .= sprintf("[%s]\n", $k);
        $output .= self::write_keys($v);
      }

    os::write($filename, $output);
  }

  private static function write_keys(array $data)
  {
    $output = "";

    foreach ($data as $k => $v)
      if (!is_array($v) and !empty($v))
        $output .= (false === strpos($v, " "))
          ? sprintf("%s = %s\n", $k, $v)
          : sprintf("%s = \"%s\"\n", $k, $v);

    return $output;
  }
}
