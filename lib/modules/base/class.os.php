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
  public static function path(array $components)
  {
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
}
