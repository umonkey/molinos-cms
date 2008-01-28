<?php

require(realpath(dirname(__FILE__).'/../lib/bootstrap.php'));

header('Content-Type: text/plain; charset=utf-8');

ob_start();

$error = null;
$errormsg = null;

$pdo = PDO_Singleton::getInstance();

mcms_log('cron', null, t("Планировщик запущен."));

foreach (bebop_get_interface_map('iScheduler') as $class) {
  if (class_exists($class)) {
    try {
      $pdo->beginTransaction();

      call_user_func(array($class, 'taskRun'));

      $pdo->commit();
    } catch (Exception $e) {
      $pdo->rollback();

      $error = $class;
      $errormsg = $e->getMessage();

      break;
    }
  }
}

if (null === $error)
  mcms_log('cron', null, t("Планировщик завершил работу без ошибок."));
else
  mcms_log('cron', null, t("Планировщик прерван: ошибка в обработчике %class: %message.", array(
    '%class' => $error,
    '%message' => $errormsg,
    )));

print 'CRON END';

die(ob_get_clean());
