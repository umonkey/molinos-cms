<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BebopMimeMail
{
  public static function send($from, $to, $subject, $body, array $attachments = null, array $headers = null)
  {
    if (empty($to)) {
      Logger::trace("mail not sent: no recipients: {$subject}");
      return;
    }

    $config = Context::last()->config;

    if (empty($from))
      if (!($from = $config->get('modules/mail/from')))
        $from = "Molinos.CMS <no-reply@" . url::host() . ">";

    if (strstr($body, '<html>') === false)
      $body = '<html><head><title>'. html::plain($subject) .'</title></head><body>'. $body .'</body></html>';

    if (!is_array($to))
      $to = preg_split('/, */', $to, -1, PREG_SPLIT_NO_EMPTY);

    Logger::log(sprintf('to=%s, subject=%s', join(',', $to), $subject));

    $mail = new htmlMimeMail();

    if ('smtp' == ($transport = ($server = $config->get('modules/mail/server')) ? 'smtp' : 'mail'))
      $mail->setSMTPParams($server);

    $mail->setFrom($from);
    $mail->setSubject($subject);
    $mail->setHtml(self::fixhtml($body));

    $mail->setTextCharset('UTF-8');
    $mail->setTextEncoding('base64');
    $mail->setHTMLCharset('UTF-8');
    $mail->setHTMLEncoding('UTF-8');
    $mail->setHeadCharset('UTF-8');

    foreach ((array)$attachments as $file)
      $mail->addAttachment($file['data'], $file['name'], $file['type']);

    foreach ((array)$headers as $k => $v)
      if (!empty($v))
        $mail->setHeader($k, $v);

    return $mail->send($to, $transport);
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.mail
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'from' => array(
        'type' => 'EmailControl',
        'label' => t('Адрес отправителя'),
        ),
      ));
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

        $new = 'http://'. url::host() . mcms::path() .'/'. $href;

        $new = str_replace($href, $new, $m[0][$idx]);
        $html = str_replace($m[0][$idx], $new, $html);
      }
    }

    return $html;
  }
};
