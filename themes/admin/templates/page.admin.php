<?php

function render_notifications()
{
  if (!empty($_GET['created']) and !empty($_GET['type'])) {
    switch ($_GET['type']) {
    case 'domain':
      return;
    }

    $map = array(
      'domain' => 'Страница добавлена, <a href="@url">настроить</a>?',
      );

    if (array_key_exists($_GET['type'], $map)) {
      $output = t($map[$_GET['type']], array(
        '%id' => $_GET['created'],
        '@url' => '?q=admin/content/edit/'. $_GET['created']
          .'&destination=CURRENT',
        ));
      return mcms::html('div', array(
        'class' => 'notification',
        ), $output);
    }
  }
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
        mcms::extras('themes/all/jquery/jquery.js');
        mcms::extras('themes/all/jquery/ui/ui.core.js');
        mcms::extras('themes/all/jquery/ui/ui.sortable.js');
        mcms::extras('themes/all/jquery/plugins/jquery.suggest.js');
        mcms::extras('themes/all/jquery/plugins/jquery.suggest.css');
        mcms::extras('themes/all/jquery/plugins/jquery.ifixpng.js');
        mcms::extras('themes/all/jquery/plugins/jquery.mcms.tabber.js');
        mcms::extras('themes/all/jquery/plugins/jquery.dimensions.js');
        mcms::extras('themes/all/jquery/plugins/jquery.bgiframe.js');
        mcms::extras('themes/admin/js/bebop.js');
      } elseif (!empty($_GET['mcmsarchive'])) {
        mcms::extras('themes/admin/js/picker-redux.js');
        print '<script type="text/javascript">var picker = "'.
          $_GET['picker'] .'";</script>';
      } else {
        mcms::extras('lib/modules/tinymce/editor/tiny_mce_popup.js', false);
        mcms::extras('lib/modules/attachment/control.attachment.js');
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
  </head>
  <body>
    <div id="preloaded_images"></div>

    <div id="all">
      <?php if (empty($_GET['picker'])): ?>
      <div id='top_menu_controls'><?=$dashboard?></div>
  
      <div id="navbar">
        <div id="top_toolbar">
          <div class="right">
            <?php print render_username(); ?>
            <a href="<?php print $base; ?>" title="Перейти на сайт"><img src="themes/admin/img/icon-home.png" alt="home" width="16" height="16" /></a>
            <?php print get_dba_link(); ?>
            <a href="<?php print mcms_plain(l('?q=admin.rpc&action=reload&destination=CURRENT')); ?>&amp;reload=1&amp;flush=1" title="Очистить кэш"><img src="themes/admin/img/icon-reload.png" alt="reload" width="16" height="16" /></a>
            <a href="?q=base.rpc&amp;action=logout" id="lnk_exit" title="Выйти"><img src="themes/admin/img/icon-exit.png" alt="logout" width="16" height="16" /></a>
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
      <div class="signature">
        <hr/><?=mcms::getSignature(null, true);?>
      </div>
    </div>
    <?php endif; ?>
  </body>
</html>
