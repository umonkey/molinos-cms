<?php

require_once(dirname(__FILE__) .'/lib/bootstrap.php');

if (!bebop_is_debugger())
  die("Access Denied.");

$um = new UpdateManager();
$um->runUpdates(array(
  'tables',
  'types',
  'reindex',
  'users',
  'ui',
  'access',
  ));

bebop_redirect('/admin/');
