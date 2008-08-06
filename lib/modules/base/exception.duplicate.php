<?php
/**
 * Исключение: нарушение уникальности.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: нарушение уникальности.
 *
 * @package mod_base
 * @subpackage Exceptions
 *
 * @see NodeBase::checkUnique()
 */
class DuplicateException extends UserErrorException
{
  public function __construct($message)
  {
    parent::__construct("Нарушение уникальности", 400, "Нарушение уникальности", $message);
  }
};
