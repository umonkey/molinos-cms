<?php

require_once(dirname(__FILE__) .'/functions.inc');

_admin_check_perm();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Molinos.CMS</title>
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/bebop.css" />
    <link rel="stylesheet" type="text/css" href="<?=$prefix?>/css/colors-green.css" />

    <?php if (empty($_GET['window']) or 'find' != $_GET['window']): ?>
    <script language="javascript" type="text/javascript" src="/lib/modules/tinymce/editor/tiny_mce_popup.js"></script>
    <script src="<?=$prefix?>/js/picker.js" type="text/javascript" language="javascript"></script>
    <?php else: ?>
    <script src="<?=$prefix?>/../all/jquery/jquery.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/js/bebop.js" type="text/javascript" language="javascript"></script>
    <script src="<?=$prefix?>/js/picker-redux.js" type="text/javascript" language="javascript"></script>
    <?php endif; ?>

    <base href="<?=$page['base']?>" />
  </head>
  <body>
    <div id='picker_wrapper'>
      <div id='center'><?=$widgets['BebopFiles']?></div>
    </div>
  </body>
</html>
