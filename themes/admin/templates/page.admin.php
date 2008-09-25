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
  $user = mcms::user()->getRaw();

  $link = '?q=admin&cgroup=access&mode=edit&id='. mcms::user()->id
    .'&destination=CURRENT';

  $name = mcms_plain(l($user));

  return l($link, $name, array(
    'title' => 'Редактирование профиля',
    'class' => 'editprofile',
    ));
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
      '@url' => '?q=admin&cgroup=none&module=update',
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
    return mcms::html('a', array('title' => 'БД',
      'href' => 'phpminiadmin.php?showcfg=1'), 'БД');
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
        mcms::extras('themes/all/jquery/jquery.min.js');
        mcms::extras('themes/all/jquery/plugins/jquery.suggest.min.js');
        mcms::extras('themes/all/jquery/plugins/jquery.suggest.css');
        mcms::extras('themes/all/jquery/plugins/jquery.ifixpng.min.js');
        mcms::extras('themes/all/jquery/plugins/jquery.mcms.tabber.min.js');
        mcms::extras('themes/all/jquery/plugins/jquery.dimensions.min.js');
        mcms::extras('themes/all/jquery/plugins/jquery.bgiframe.min.js');
        mcms::extras('themes/admin/js/bebop.min.js');
      } elseif (!empty($_GET['mcmsarchive'])) {
        mcms::extras('themes/admin/js/picker-redux.js');
        print '<script type="text/javascript">var picker = "'.
          $_GET['picker'] .'";</script>';
      } else {
        mcms::extras('lib/modules/tinymce/editor/tiny_mce_popup.js', false);
        mcms::extras('lib/modules/base/control.attachment.js');
      }
    ?>
    <?php print mcms::extras(); ?>
    <link rel="shortcut icon" href="themes/all/img/favicon.ico"
      type="image/x-icon" />
    <link rel="home" href="admin" />
    <link rel="contents" href="?q=admin/content/tree/taxonomy" />
    <link rel="index" href="?q=admin/content/list&amp;columns=name,class,uid,created" />
    <link rel="search" type="text/html" href="?q=admin/content/search" />
    <link rel="help" href="http://code.google.com/p/molinos-cms/w/list?can=2&amp;q=label%3AFAQ&amp;sort=&amp;colspec=Summary+Changed+ChangedBy&amp;nobtn=Update" />
    <link rel="copyright" href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html" />
    <link rel="glossary" href="?q=admin/structure/list/schema" />
    <link rel="author" href="http://www.molinos-cms.ru/" />
    <script type="text/javascript">var mcms_path = '<?php
      print mcms::path(); ?>';</script>
  </head>
  <body>
    <div id="preloaded_images"></div>

    <div id="all">
      <?php if (empty($_GET['picker'])): ?>
      <?php print render_dashboard($dashboard); ?>
  
      <div id="navbar">
        <div id="top_toolbar">
          <div class="right">
            <?php print render_username(); ?>
            <a href="<?php print $base; ?>" title="Перейти на сайт"><img src="themes/admin/img/icon-home.png" alt="home" width="16" height="16" /></a>
            <?php print get_dba_link(); ?>
            <a href="<?php print l('admin.rpc?action=reload&destination=CURRENT'); ?>&amp;reload=1&flush=1" title="Очистить кэш"><img src="themes/admin/img/icon-reload.png" alt="reload" width="16" height="16" /></a>
            <a href="base.rpc?action=logout" id="lnk_exit" title="Выйти"><img src="themes/admin/img/icon-exit.png" alt="logout" width="16" height="16" /></a>
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
      <img src="themes/admin/img/siteimage/logo_molinos_btm.png" alt="Molinos.Ru" align="middle" />
      <span>Версия <?php print get_version_info(); ?></span>
    </div>
    <?php endif; ?>
  </body>
</html>
