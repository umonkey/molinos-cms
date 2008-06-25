<?php

$tmp = bebop_split_url();
$tmp['args']['login'] = 'error';

$laction = bebop_combine_url(array(
  'path' => 'base.rpc',
  'args' => array(
    'action' => 'login',
    'onerror' => bebop_combine_url($tmp, false),
    'destination' => $_SERVER['REQUEST_URI'],
    ),
  ));

// Ссылка на восстановление пароля (?login=error).
$lforgot = bebop_combine_url($tmp);

// Ссылка на вход (первоначальная).
$tmp['args']['login'] = null;
$lreturn = bebop_combine_url($tmp);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title><?php print $_SERVER['HTTP_HOST']; ?> — авторизация</title>
    <?php empty($base) or print mcms::html('base', array('href' => $base)); ?>
    <meta http-equiv='Content-Type' content='application/xhtml+xml; charset=UTF-8' />
    <meta name='robots' content='index,follow' />
    <link rel='shortcut icon' href='themes/all/img/lock.png' type='image/png' />
    <link rel='stylesheet' href='themes/all/401.css' type='text/css' />
    <link rel='stylesheet' href='themes/all/refpoint.reset.css' type='text/css' />
    <link rel='stylesheet' href='themes/all/refpoint.typography-16.css' type='text/css' />

    <script type='text/javascript' language='javascript'>function show_restore() { alert('restore'); document.getElementById('user-login-form').style.display = 'none'; document.getElementById('profile-remind-form').style.display = 'block'; return false; } function show_login() { document.getElementById('user-login-form').style.display = 'block'; document.getElementById('profile-remind-form').style.display = 'none'; return false; }</script>
  </head>
  <body>
    <div class="halfheight">&nbsp;</div>
    <div id='form-user-login-form-wrapper'>
      <h1 id="logo"><a><span>Molinos.CMS</span></a></h1>
      <form method='post' action='<?php print $laction; ?>' id='user-login-form' <?php if (empty($_GET['login'])) print "class='active' " ?>enctype='multipart/form-data'>
        <fieldset>
          <legend>Авторизация</legend>
          <div class='control control-TextLineControl-wrapper'>
            <label for='unnamed-ctl-2' class='required'>Email:</label>
            <input type='text' id='unnamed-ctl-2' class='form-text' name='login' value='' />
          </div>
          <div class='control control-PasswordControl-wrapper'>
            <label for='unnamed-ctl-3' class='required'>Пароль:</label>
            <input type='password' id='unnamed-ctl-3' class='form-text' name='password' value='' />
            <a class="restorepass_sh" href="<?php print $lforgot; ?>" onclick="javascript:return show_restore();">забыли пароль?</a>
          </div>
          <div class='control control-SubmitControl-wrapper'>
            <input type='submit' class='form-submit' value='Войти' />
          </div>
        </fieldset>
      </form>
      <form method='post' action='base.rpc?action=restore' id='profile-remind-form' <?php if (!empty($_GET['login'])) print "class='active' " ?>enctype='multipart/form-data'>
        <fieldset>
          <legend>Напоминание пароля</legend>
          <div class='intro'>Введите почтовый адрес, который вы использовали при регистрации.  Инструкция по восстановлению пароля будет отправлена на этот адрес.</div>
          <div class='control control-TextLineControl-wrapper'>
            <!--
            <div class='label'>
              <label for='unnamed-ctl-1' class='required'>Логин или почтовый адрес:</label>
            </div>
            -->
            <input type='text' id='unnamed-ctl-1' class='form-text' name='identifier' value='' />
          </div>
          <div class='control control-SubmitControl-wrapper'>
            <input type='submit' class='form-submit' value='Напомнить' /> <a class="restorepass_sh" href="<?php print $lreturn; ?>" onclick="javascript:return show_login();">вернуться к авторизации</a>
          </div>
        </fieldset>
      </form>
    </div>
  </body>
</html>
