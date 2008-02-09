<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

function smarty_function_format_bytes($params, &$smarty)
{
    if (!array_key_exists('bytes', $params)) {
        $smarty->trigger_error("format_bytes: missing 'bytes' parameter");
        return;
    }

    if (!is_numeric($params['bytes'])) {
        $smarty->trigger_error("format_bytes: 'bytes' parameter should be numeric");
        return;
    }

    $measures = array('B', 'KB', 'MB', 'GB', 'TB');
    $result = intval($params['bytes']);

    settype($result, 'float');

    foreach ($measures as $m) {
        $x = $result / 1024;
        if ($x < 1)
            break;
        $result = $x;
    }

    $result = floor($result * 100) / 100;

    return $result." ".$m;
}

?>