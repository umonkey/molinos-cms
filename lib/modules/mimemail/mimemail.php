<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(dirname(__FILE__) .'/htmlMimeMail.php');

class BebopMimeMail implements iModuleConfig
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
    $mail->setHtml($body);

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
};
