<?php
/**
 * Исключение: ошибка в действиях пользователя.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: ошибка в действиях пользователя.
 *
 * @package mod_base
 * @subpackage Exceptions
 */
class UserErrorException extends Exception
{
  /**
   * Описание ошибки.
   */
  var $description = null;

  /**
   * Дополнительный текст, подробно описывающий проблему.
   * @todo устранить.
   */
  var $note = null;

  /**
   * Расширенный конструктор.
   *
   * @param string $message заголовок сообщения.
   *
   * @param integer $code HTTP код ошибки.
   *
   * @param string $description текст сообщения об ошибке.
   * @todo устранить.
   *
   * @param string $note дополнительный текст ошибки.
   * @todo устранить.
   */
  public function __construct($message, $code, $description = null, $note = null)
  {
    parent::__construct($message, $code);
    $this->description = $description;
    $this->note = $note;
  }

  /**
   * Возвращает расширенное описание ошибки.
   * @todo устранить.
   *
   * @return string описание ошибки.
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Возвращает расширенное описание ошибки.
   * @todo устранить.
   *
   * @return string описание ошибки.
   */
  public function getNote()
  {
    return $this->note;
  }
};
