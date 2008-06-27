<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BebopMimeMail implements iModuleConfig
{
  public static function send($from, $to, $subject, $body, array $attachments = null, array $headers = null)
  {
    if (empty($to))
      throw new InvalidArgumentException(t('Получатель сообщения не указан.'));

    if (empty($from))
      if (($from = mcms::config('mail_from')) === null)
        $from = "Molinos.CMS <no-reply@{$_SERVER['HTTP_HOST']}>";

    if (strstr($body, '<html>') === false)
      $body = '<html><head><title>'. mcms_plain($subject) .'</title></head><body>'. $body .'</body></html>';

    if (!is_array($to))
      $to = preg_split('/, */', $to, -1, PREG_SPLIT_NO_EMPTY);

    mcms::log('mail', t('to=%to, subject=%subject', array('%to' => join(',', $to), '%subject' => $subject)));

    $mail = new htmlMimeMail();

    $mail->setSMTPParams(mcms::config('mail_server'));

    $mail->setFrom($from);
    $mail->setSubject($subject);
    $mail->setHtml(self::fixhtml($body));

    $mail->setTextCharset('UTF-8');
    $mail->setTextEncoding('base64');
    $mail->setHTMLCharset('UTF-8');
    $mail->setHTMLEncoding('UTF-8');
    $mail->setHeadCharset('UTF-8');

    if (!empty($attachments)) {
      foreach ($attachments as $file) {
        $mail->addAttachment($file['data'], $file['name'], $file['type']);
      }
    }

    if (null !== $headers)
      foreach ($headers as $k => $v)
        if (!empty($v))
          $mail->setHeader($k, $v);

    return $mail->send($to);
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());
    $form->addControl(new EmailControl(array(
      'value' => 'config_from',
      'label' => t('Адрес отправителя'),
      'default' => mcms::config('mail_from'),
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
  }

  // Превращает все относительные ссылки в абсолютные.
  private static function fixhtml($html)
  {
    $re = '@<a(\s+([a-z]+)=([\'"]([^\'"]+)[\'"]))+\s*>@i';

    if (preg_match_all($re, $html, $m)) {
      foreach ($m[4] as $idx => $href) {
        if (false !== strpos($href, '://'))
          continue;
        if (false !== strpos($href, 'mailto:'))
          continue;

        $new = 'http://'. $_SERVER['HTTP_HOST']
          .'/'. trim(dirname($_SERVER['SCRIPT_NAME']), '/') .'/'
          .ltrim($href, '/');

        $new = str_replace($href, $new, $m[0][$idx]);
        $html = str_replace($m[0][$idx], $new, $html);
      }
    }

    return $html;
  }
};
