<?php
/**
 * Исключение: виджет подавлен.
 *
 * @package mod_base
 * @subpackage Exceptions
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Исключение: виджет подавлен.
 *
 * Этим исключением виджет сообщает, что он не может работать в таких условиях.
 * Кидается обычно в методе getRequestOptions() виджета, для подавления вызова
 * методов onGet() и onPost().
 *
 * До пользователя это исключение не доходит: оно гасится в недрах
 * класса RequestController.
 *
 * @package mod_base
 * @subpackage Exceptions
 */
class WidgetHaltedException extends Exception
{
  public function __construct($message = null)
  {
    parent::__construct($message);
  }
};
