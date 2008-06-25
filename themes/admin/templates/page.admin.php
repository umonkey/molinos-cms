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

  if (mcms::version() != ($available = mcms::version(mcms::VERSION_AVAILABLE))) {
    $version .= t(' — <a href=\'@url\'>обновить</a>', array(
      '@url' => 'admin?cgroup=none&module=update',
      ));
  }

  // $version .= t(' — <a href=\'@url\'>проверить обновления</a>', array('@url' => '/admin/?mode=update'));

  return $version;
}

function get_dba_link()
{
  if ('MySQL' != mcms::db()->getDbType())
    return;
  
  if (is_readable(MCMS_ROOT .'/phpminiadmin.php'))
    return "<a target='_blank' href='". MCMS_PATH ."phpminiadmin.php?showcfg=1'>БД</a>";
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Molinos.CMS</title>
    <?php empty($base) or print mcms::html('base', array('href' => $base)); ?>

    <?php
      mcms::extras('themes/admin/css/bebop.css');
      mcms::extras('themes/admin/css/style.css');
      mcms::extras('themes/admin/css/notification.css');
      mcms::extras('themes/admin/css/topmenu.css');
      mcms::extras('themes/admin/css/colors-green.css');

      if (empty($_GET['picker'])) {
        mcms::extras('themes/all/jquery/jquery.js');
        mcms::extras('themes/all/jquery/plugins/jquery.suggest.js');
        mcms::extras('themes/all/jquery/plugins/jquery.suggest.css');
        mcms::extras('themes/all/jquery/plugins/jquery.ifixpng.js');
        mcms::extras('themes/all/jquery/plugins/jquery.mcms.tabber.js');
        mcms::extras('themes/all/jquery/plugins/jquery.dimensions.js');
        mcms::extras('themes/all/jquery/plugins/jquery.bgiframe.js');
        mcms::extras('themes/all/jquery/plugins/jquery.MultiFile.js');
        mcms::extras('themes/admin/js/bebop.js');
      } elseif (!empty($_GET['window']) and 'find' == $_GET['window']) {
        mcms::extras('lib/modules/tinymce/editor/tiny_mce_popup.js');
        mcms::extras('themes/admin/js/picker-redux.js');
      } else {
        // mcms::extras('lib/modules/tinymce/editor/tiny_mce_popup.js');
        mcms::extras('lib/modules/base/control.attachment.js');
      }
    ?>
    <?php print mcms::extras(); ?>
    <link rel="shortcut icon" href="themes/admin/icon.png" type="image/png" />
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
            <?php print l('admin?cgroup=access&mode=edit&id='. mcms::user()->id .'&destination=CURRENT', 'Настройки', array('title' => 'Редактирование профиля')); ?>
            <?php print get_dba_link(); ?>
            <a href="<?php print l('admin.rpc?action=reload&destination=CURRENT'); ?>&amp;reload=1&flush=1" title="Сбрасывает кэш и сканирует модули, это медленно!">Перезагрузка</a>
            <?php print l('base.rpc?action=logout&destination=/', 'Выйти', array('id' => 'lnk_exit')); ?>
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
      <img src="themes/admin/img/siteimage/logo_molinos_btm_ico.gif" alt="Molinos.Ru" align="middle" />
      <img src="themes/admin/img/siteimage/logo_molinos_btm.gif" alt="Molinos.Ru" align="middle" />
      <span>Версия <?php print get_version_info(); ?></span>
    </div>
    <?php endif; ?>
  </body>
</html>
