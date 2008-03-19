<?php

function render_dashboard(array $result)
{
  $controls = $items = array();
  $index = $groupidx = 1;

  $oldgroup = null;

  foreach ($result as $group => $icons) {
    if ($oldgroup !== $group) {
      $controls[] = mcms::html('a', array(
        'href' => '#'. $index,
        ), mcms_plain($group));
    }

    foreach ($icons as $icon) {
      if (!empty($icon['img'])) {
        $html = '<li class=\'group-'. $groupidx .'\'>';
        $html .= mcms::html('a', array(
          'href' => $icon['href'],
          'style' => 'display: block; background: transparent url('. $icon['img'] .') no-repeat top center; padding: 85px 2px 0 2px; text-align: center',
          'description' => isset($icon['description']) ? $icon['description'] : null,
          ), mcms_plain($icon['title']));
        $html .= '</li>';

        $items[] = $html;
      }

      $index++;
    }

    $groupidx++;
  }

  $html = '<div class=\'carousel-control\'>'. join('', $controls) .'</div>';
  $html .= '<div class=\'carousel jcarousel-skin-tango\'>';
  $html .= '<ul>'. join('', $items) .'</ul>';
  $html .= '</div>';

  return $html;
}

function render_notifications()
{
}

function render_username()
{
  $user = mcms::user();

  return $user->getName();
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Molinos.CMS</title>
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/bebop.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/style.css" />
    <link rel="stylesheet" type="text/css" href="/themes/admin/css/notification.css" />

    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jquery.jcarousel.css" />
    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jquery.suggest.css" />
    <link rel="stylesheet" type="text/css" href="/themes/all/jquery/plugins/jcarousel-skins/tango/skin.css" />

    <script type="text/javascript" language="javascript" src="/themes/all/jquery/jquery.js"></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.jcarousel.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.ifixpng.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.formtabber.js"></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.autogrow.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/all/jquery/plugins/jquery.suggest.js" ></script>
    <script type="text/javascript" language="javascript" src="/themes/admin/js/bebop.js" ></script>
  </head>
  <body>
    <div id="preloaded_images"></div>

    <div id="all">
      <div id="top_toolbar">
        <div class="right">
          <div class="greeting">Здравствуйте, <?php print render_username(); ?></div>
          <a href="/admin/?mode=edit&id=<?php print mcms::user()->getUid(); ?>&destination=<?php print urlencode($_SERVER['REQUEST_URI']) ?>">Настройки</a>
          <div>
            <a id="lnk_exit" href="/admin/?mode=logout&amp;destination=%2F">Выйти</a>
          </div>
        </div>
      </div><!-- id=top_toolbar -->

      <?php print render_dashboard($dashboard); ?>
  
      <script type="text/javascript">
      // Включение карусели в основной навигации
      if ($('.carousel').length != 0) {
        var carousel = $('.carousel').jcarousel({
          scroll: 5,
          visible: 10,
          initCallback: bebop_dashboard_init
        });
      }
      </script>

      <div id="content_wrapper">
        <div id="center">
          <?php print render_notifications(); ?>
          <?php print $content; ?>
        </div><!-- id="center" -->

        <div id="left_sidebar">
          <!-- _admin_actions() ??? -->
        </div>

      </div><!-- id="content_wrapper" -->

      <div id="footer_spacer"></div>
    </div><!-- all -->

    <div id="footer">
      <img src="/themes/admin/img/siteimage/logo_molinos_btm_ico.gif" alt="Molinos.Ru" align="middle" />
      <img src="/themes/admin/img/siteimage/logo_molinos_btm.gif" alt="Molinos.Ru" align="middle" />
      <span>Версия <?php print BEBOP_VERSION ?></span>
    </div>
  </body>
</html>
