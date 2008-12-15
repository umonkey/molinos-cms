<?php

require dirname(__FILE__) .'/../lib/bootstrap.php';

Loader::rebuild($local = !file_exists($conf = 'conf' . DIRECTORY_SEPARATOR . 'default.config.php'));

if ($local and file_exists($conf))
  unlink($conf);
