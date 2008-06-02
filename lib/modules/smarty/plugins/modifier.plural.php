<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     modifier.plural.php
 * Type:     modifier
 * Name:     plural
 * Purpose:  generate 1 of 3 given plural forms in russian
 * -------------------------------------------------------------
 */
 
function smarty_modifier_plural($n, $form1, $form2, $form5) {

    if (empty($n)) {
        $smarty->trigger_error("plural: missing 'n' parameter");
        return;
    }
    
    if (!is_numeric($n)) {
        $smarty->trigger_error("plural: 'n' parameter should be numeric");
        return;
    }

    if (empty($form1) || empty($form2) || empty($form5)) {
        $smarty->trigger_error("plural: missing one of 'form' parameters");
        return;
    }
    
    $n = abs($n) % 100;
    $n1 = $n % 10;
    
    if ($n > 10 && $n < 20)
        return "$n " . $form5;
    if ($n1 > 1 && $n1 < 5)
        return "$n " . $form2;
    if ($n1 == 1)
        return "$n " . $form1;
    return "$n " . $form5;
    
}

?>
