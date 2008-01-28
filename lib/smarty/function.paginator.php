<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

function smarty_function_paginator($params, &$smarty)
{
    if (!array_key_exists('src', $params) or !is_array($params['src'])) {
        $smarty->trigger_error("paginator: missing 'src' parameter or 'src' is not array");
        return;
    }

    if (!array_key_exists('assign', $params) or !is_string($params['assign'])) {
        $smarty->trigger_error("paginator: missing 'assign' parameter or 'assign' is not a valid name of the variable");
        return;
    }

    $src = $params['src'];
    if (!isset($src['total_pages']) or !isset($src['current_page']) or !isset($src['per_page'])) {
        $smarty->trigger_error("paginator: 'src' array is not well-formed");
        return;
    }

    if (isset($params['number']) and is_numeric($params['number']))
        $number = intval($params['number']);
    else
        $number = 10;

    $result = array(
        'numbers' => _bebop_paginator($src['total_pages'], $src['current_page'], $src['per_page'], $number)
    );

    if ($src['current_page'] > 1) {
        $result['first'] = 'offset=0';
        $result['prev'] = 'offset='.(($src['current_page'] - 1) * $src['per_page']);
    }

    if ($src['current_page'] < ($src['total_pages'] - 1)) {
        $result['last'] = 'offset='.(($src['total_pages'] - 1) * $src['per_page']);
        $result['next'] = 'offset='.(($src['current_page'] + 1) * $src['per_page']);
    }

    $smarty->assign($params['assign'], $result);
}

function _bebop_paginator($total_pages, $current_page, $per_page, $number)
{
    if ($number > $total_pages)
        $number = $total_pages;

    $head = floor($number / 2);
    $tail = $number - $head;

    if ($current_page < $head) {
        $diff = $head - $current_page;
        $head -= $diff;
        $tail += $diff;
    } elseif ($current_page > $total_pages - $tail) {
        $diff = $tail - ($total_pages - $current_page);
        $tail -= $diff;
        $head += $diff;
    }

    $result = array();
    for ($i = $current_page - $head; $i < $current_page + $tail; $i++) {
        $result[$i+1] = 'offset='.($per_page * $i);
    }

    return $result;
}
