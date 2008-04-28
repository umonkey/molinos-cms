<?php

function render_dashboard($result)
{
  return '<div id=\'top_menu_controls\'>'. $result .'</div>';
}

function render_notifications()
{
}

function render_username()
{
  $user = mcms::user();
  return empty($user->fullname) ? $user->name : $user->fullname;
}

function render_reboot_link()
{
  $url = bebop_split_url();
  $url['args']['reload'] = 1;
  return bebop_combine_url($url, true);
}

function get_version_info()
{
  $version = preg_replace('/^(\d+)\.(\d+)\.(\d+)$/', '<a href=\'http://code.google.com/p/molinos-cms/wiki/ChangeLog_\1\2\'>\1.\2.\3</a>', mcms::version());

  $version .= ' ('. mcms::db()->getDbType();

  if (count($tmp = explode(':', mcms::config('dsn'))))
    $version .= $tmp[0];

  if (null !== ($tmp = BebopCache::getInstance())) {
    switch (get_class($tmp)) {
    case 'APC_Cache':
      $version .= '+APC';
      break;
    case 'MemCacheD_Cache':
      $version .= '+memcache';
      break;
    }
  }

  $version .= '+'. ini_get('memory_limit') .')';

  // $version .= t(' — <a href=\'@url\'>проверить обновления</a>', array('@url' => '/admin/?mode=update'));

  return $version;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Molinos.CMS</title>
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/bebop.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/style.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/notification.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/topmenu.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/colors-green.css" />

    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jquery.suggest.css" />

    <script type="text/javascript" language="javascript" src="/themes/all/jquery/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.ifixpng.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.formtabber.js"></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.dimensions.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.bgiframe.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.suggest.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/admin/js/bebop.js" ></script>
  </head>
  <body>
    <div id="preloaded_images"></div>

    <div id="all">
      <?php if (empty($_GET['picker'])): ?>
      <?php print render_dashboard($dashboard); ?>
  
      <div id="navbar">
        <div id="top_toolbar">
          <div class="right">
            <div class="greeting">Здравствуйте, <?php print render_username(); ?>.</div>
            <a href="/admin/?cgroup=access&amp;mode=edit&amp;id=<?php print mcms::user()->id; ?>&amp;destination=<?php print urlencode($_SERVER['REQUEST_URI']) ?>" title="Редактировать свой профиль">Настройки</a>
            <a href="<?php print render_reboot_link(); ?>" title="Сбрасывает кэш и сканирует модули, это медленно!">Перезагрузка</a>
            <a id="lnk_exit" href="/base.rpc?action=logout&amp;destination=%2F">Выйти</a>
          </div>
        </div><!-- id=top_toolbar -->

        <div id="top_menu_controls_bottom"></div>
      </div>
      <?php endif; ?>

      <div id="content_wrapper">
        <div id="center">
          <?php print render_notifications(); ?>
          <?php print $content; ?>
        </div><!-- id="center" -->

        <div id="left_sidebar">
          <!-- _admin_actions() ??? -->
        </div>

      </div><!-- id="content_wrapper" -->

      <?php if (empty($_GET['picker'])): ?>
        <div id="footer_spacer"></div>
      <?php endif; ?>
    </div><!-- all -->

    <?php if (empty($_GET['picker'])): ?>
    <div id="footer">
      <img src="/themes/admin/img/siteimage/logo_molinos_btm_ico.gif" alt="Molinos.Ru" align="middle" />
      <img src="/themes/admin/img/siteimage/logo_molinos_btm.gif" alt="Molinos.Ru" align="middle" />
      <span>Версия <?php print get_version_info(); ?></span>
    </div>
    <?php endif; ?>
  </body>
</html>
