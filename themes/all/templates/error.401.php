<?php

$tmp = bebop_split_url();
$tmp['args']['remind'] = '1';

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
$tmp['args']['remind'] = null;
$lreturn = bebop_combine_url($tmp);

mcms::extras('themes/all/styles/lib/refpoint.reset.css');
mcms::extras('themes/all/styles/lib/refpoint.typography-16.css');
mcms::extras('themes/all/styles/pages.401.css');

function __get($key)
{
  return array_key_exists($key, $_GET) ? $_GET[$key] : null;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
  <head>
    <title><?php print $_SERVER['HTTP_HOST']; ?> &mdash; авторизация</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="robots" content="none" />
    <?php empty($base) or print mcms::html('base', array('href' => $base)); ?>
    <link rel="shortcut icon" href="themes/all/img/favicon.ico"
      type="image/x-icon" />
    <?php print mcms::extras(); ?>
    <!--[if gte IE 6]>
      <link rel="stylesheet" href="themes/all/styles/pages.401.ie.css"
        type="text/css" />
    <![endif]-->
  </head>
  <body>

    <div id="form-user-login-form-wrapper">

    <?php if (empty($_GET['remind'])) { ?>
        <form method="post" action="<?php print $laction; ?>" enctype="multipart/form-data" id="user-login-form">
          <fieldset>
              <legend><span>Авторизация</span></legend>
              <div class="control">
                <label>
                  <span>Логин (эл. почта или OpenID):</span>
                  <input class="text login" type="text" name="login" />
                </label>
              </div>
              <div class="control">
                <label>
                  <span>Пароль:</span>
                  <input class="text password" type="password" name="password" />
                </label>
                <span class="tip">Если забыли, можем  <a href="<?php print $lforgot; ?>" class="switchforms">напомнить</a>.</span>
              </div>
            </fieldset>
            <div class="submit-wrapper">
              <input class="submit" type="submit" value="Войти"/>
              <?php if ('error' == __get('login')) { ?>
                <span class="message error"><strong>Ошибка</strong>: <?php print $error['description']; ?></span>
              <?php } ?>
            </div>
          </form>

        <?php } elseif (1 == __get('remind') or 'notfound' == __get('remind')) { ?>
        <form method='post' action='?q=base.rpc&amp;action=restore' enctype='multipart/form-data' id='profile-remind-form-nojs'>
          <fieldset>
            <legend><span>Напоминание пароля</span></legend>
            <div class='control'>
              <label>
                <span>Почтовый адрес:</span>
                <input type='text' class='text login' name='identifier' value='<?php print mcms_plain(__get('remind_address')); ?>' />
              </label>
            <?php if ($_GET['remind'] == 'notfound') { ?>
              <p class='intro'>Пользователь с таким адресом нам неизвестен.</p>
            <?php } else { ?>
              <p class='intro'>Инструкция по восстановлению пароля будет отправлена на этот адрес.</p>
            <?php } ?>
            </div>
            <input type='hidden' name='destination' value='<?php print mcms_plain($_SERVER['REQUEST_URI']); ?>' />
          </fieldset>
          <div class='submit-wrapper'>
            <input type='submit' class='form-submit' value='Напомнить' />
            <a class="switchforms" href="<?php print $lreturn; ?>">назад к авторизации</a>
          </div>
        </form>

      <?php } elseif ('mail_sent' == __get('remind')) { ?>
        <div>Инструкция для входа в систему отправлена на указанный почтовый
          адрес. Это окно можно закрыть.</div>
      <?php } ?>

    </div>

  </body>
</html>
