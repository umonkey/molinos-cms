<?php
/**
 * Статистика базы данных.
 *
 * @package mod_admin
 * @subpackage Core
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Провайдер статистики БД.
 *
 * Выводит статистику БД на главной странице административного интерфейса.
 *
 * @package mod_admin
 * @subpackage Core
 */
class AdminStatus
{
  /**
   * @mcms_message ru.molinos.cms.admin.status
   */
  public static function getMenuIcons(Context $ctx, array &$icons)
  {
    $user = $ctx->user;

    if ('admin' != $_GET['q'])
      return $icons;

    if (!($p = $user->password) or $p == md5(''))
      if (0 !== strpos($user->name, 'http://'))
        $icons[] = array(
          'group' => 'status',
          'message' => t('Пожалуйста, установите пароль на ваш аккаунт.'),
          'link' => '?q=admin/edit/' . $user->id
            . '&destination=CURRENT',
          );
  }
}
