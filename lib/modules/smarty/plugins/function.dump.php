<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_dump($params, &$smarty)
{
  mcms::debug($params);
}
