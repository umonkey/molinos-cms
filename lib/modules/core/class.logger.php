<?php

class Logger
{
  public static function log($message, $condition = null)
  {
    $constant = null;

    if (null !== $condition) {
      $dname = 'MCMS_FLOG_' . strtoupper($condition);
      if (defined($dname) and ($constant = constant($dname)) === false)
        return;
    }

    if (null === $condition)
      error_log($message, 0);
    elseif ($constant and is_string($constant))
      error_log($message . "\n", 3, $constant);
    else
      error_log('[' . $condition . '] ' . $message, 0);
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

    error_log(' -- ' . MCMS_REQUEST_URI, 0);
  }

  /**
   * Форматирует содержимое стэка.
   */
  public static function backtrace($stack = null, $condition = null)
  {
    $output = '';

    if (null !== $condition and !defined($condition))
      return;

    if ($stack instanceof Exception) {
      $tmp = $stack->getTrace();
      array_unshift($tmp, array(
        'file' => $stack->getFile(),
        'line' => $stack->getLine(),
        'function' => sprintf('throw new %s', get_class($stack)),
        ));
      $stack = $tmp;
    } elseif (null === $stack or !is_array($stack)) {
      $stack = debug_backtrace();
      array_shift($stack);
    }

    $libdir = 'lib'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR;

    $idx = 1;
    foreach ($stack as $k => $v) {
      /*
      if (!empty($v['file']))
        $v['file'] = preg_replace('@.*'. preg_quote($libdir) .'@', $libdir, $v['file']);

      if (!empty($v['class']))
        $func = $v['class'] .$v['type']. $v['function'];
      else
        $func = $v['function'];
      */

      if (empty($v['file']))
        continue;

      $output .= sprintf("%2d. ", $idx++);
      if (class_exists('mcms'))
        $output .= mcms::formatStackElement($v);

      /*
      if (!empty($v['file']) and !empty($v['line']))
        $output .= sprintf('%s(%d) — ', ltrim(str_replace(MCMS_ROOT, '', $v['file']), '/'), $v['line']);
      else
        $output .= '??? — ';

      $output .= $func .'()';
      */

      $output .= "\n";
    }

    return $output;
  }
}
