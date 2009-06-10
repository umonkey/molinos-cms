<?php

class Logger
{
  const BACKTRACE = 1;

  public static function log($message, $options = null)
  {
    error_log($message, 0);

    if ($options & self::BACKTRACE) {
      foreach (debug_backtrace() as $idx => $line)
        if ($idx >= 2 and !empty($line['class']) and !empty($line['function']))
          error_log(" -- {$line['class']}{$line['type']}{$line['function']}()", 0);
    }
  }

  public static function trace($message)
  {
    $trace = debug_backtrace();

    if ($message instanceof Exception) {
      $trace = $message->getTrace();
      array_unshift($trace, array(
        'file' => $message->getFile(),
        'line' => $message->getLine(),
        'class' => 'throw',
        'type' => ' new ',
        'function' => get_class($message),
        ));

      $message = get_class($message) . ': ' . $message->getMessage();
    }

    error_log($message, 0);

    foreach ($trace as $line)
      if (!empty($line['class']) and !empty($line['function'])) {
        $file = empty($line['file'])
          ? '???'
          : os::localpath($line['file']) . ' @' . $line['line'];
        error_log(" -- {$line['class']}{$line['type']}{$line['function']}() — {$file}", 0);
      }

    error_log(" -- {$_SERVER['REQUEST_URI']}", 0);
  }
}
