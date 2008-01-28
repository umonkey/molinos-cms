<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title><?=$error['message']?></title>
    <link rel='stylesheet' type='text/css' href='<?=$prefix?>/errors.css' />
  </head>
  <body>
    <h1><a href="http://code.google.com/p/molinos-cms/"><span><?=$error['message']?></span></a></h1>
    <p class='main'>Доступ к этой части сайта ограничен.</p>
    <p>Вам нужно авторизоваться, чтобы продолжить работу.</p>

    <form method='post'>
      <input type='hidden' name='form_id' value='user-login-form' />
      <input type='hidden' name='form_handler' value='profile' />
      <input type='hidden' name='destination' value='<?=htmlspecialchars($_SERVER['REQUEST_URI'])?>' />
      <table align='center' border='0' cellspacing='0' cellpadding='4'>
        <tr>
          <td class='label'>
            <label for='ctluser'>Имя:</label>
          </td>
          <td>
            <input type='text' id='ctluser' name='login' />
          </td class='control'>
        </tr>
        <tr>
          <td class='label'>
            <label for='ctlpass'>Пароль:</label>
          </td>
          <td class='control'>
            <input type='password' id='ctlpass' name='password' />
          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td class='control'>
            <input type='submit' id='ctlsubmit' value='Войти' />
          </td>
        </tr>
      </table>
    </form>
  </body>
</html>
