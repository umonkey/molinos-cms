<?php

class BaseCron implements iScheduler
{
  public static function taskRun(Context $ctx)
  {
    $count = 0;
    $dumpdir = mcms::config('dumpdir', 'tmp/crashdump');
    $message = t('Checking for crashdumps in %dir...', array('%dir' => $dumpdir)) . '<br />';

    if (is_readable($dumpdir)) {
      if ($handle = opendir($dumpdir)) {
        while (false !== ($file = readdir($handle))) {
          if ($file != '.' && $file != '..' && is_file("{$dumpdir}/{$file}")) {
            $count++;
            $message .= t("Found crash dump file: %file", array('%file' => $file)) . '<br />';
          }
        }
        closedir($handle);
      }
    } else {
      $message = t('Can not read crashdump directory') . '<br />';
    }

    if (0 != $count and class_exists('BebopMimeMail')) {
      BebopMimeMail::send(
        mcms::config('mail.from'),
        mcms::config('backtracerecipients'),
        'Crash dump report for ' . url::host(),
        $message);
    }
  }
}
