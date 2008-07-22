<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(dirname(__FILE__) .'/htmlMimeMail.php');

class BebopMimeMail
{
  public static function send($from, $to, $subject, $body, array $attachments = null)
  {
    if (empty($from)) {
      if (($from = BebopConfig::getInstance()->mail_from) === null)
        $from = "Molinos.CMS <no-reply@{$_SERVER['HTTP_HOST']}>";
    }

    if (strstr($body, '<html>') === false)
      $body = '<html><head><title>'. mcms_plain($subject) .'</title></head><body>'. $body .'</body></html>';

    if (!is_array($to))
      $to = preg_split('/, */', $to);

    $mail = new htmlMimeMail();

    $mail->setSMTPParams(BebopConfig::getInstance()->mail_server);

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

    return $mail->send($to);
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

        $new = 'http://'. $_SERVER['HTTP_HOST'].'/';
        $dn = dirname($_SERVER['SCRIPT_NAME']);
        if (!empty($dn) and ($dn != '/'))
          $new .= trim($dn, '/') .'/';
        $new .= ltrim($href, '/');

        $new = str_replace($href, $new, $m[0][$idx]);
        $html = str_replace($m[0][$idx], $new, $html);
      }
    }

    return $html;
  }
};
